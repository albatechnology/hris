<?php

namespace App\Http\Controllers\Api;

use App\Enums\CountrySettingKey;
use App\Exports\RunPayrollExport;
use App\Http\Requests\Api\RunPayroll\UpdateUserComponentRequest;
use App\Http\Requests\Api\RunPayroll\StoreRequest;
use App\Http\Requests\Api\RunPayroll\UpdateRequest;
use App\Http\Requests\Api\RunPayroll\ExportRequest;
use App\Http\Resources\RunPayroll\RunPayrollResource;
use App\Models\Bank;
use App\Models\Company;
use App\Models\CountrySetting;
use App\Models\LoanDetail;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Services\RunPayrollService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RunPayrollController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:run_payroll_access', ['only' => ['restore']]);
        $this->middleware('permission:run_payroll_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:run_payroll_create', ['only' => 'store']);
        $this->middleware('permission:run_payroll_edit', ['only' => 'update']);
        $this->middleware('permission:run_payroll_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(RunPayroll::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('period'),
            ])
            ->allowedIncludes([
                'company',
                'branch',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'branch_id',
                'period',
                'payment_schedule',
                'created_at',
            ])
            ->paginate($this->per_page);

        return RunPayrollResource::collection($data);
    }

    public function show(int $id): RunPayrollResource
    {
        $runPayroll = RunPayroll::findTenanted($id);
        return new RunPayrollResource($runPayroll->load(['users.user', 'users.components.payrollComponent']));
    }

    public function store(StoreRequest $request)
    {
        $company = Company::find($request->company_id);
        if ($company->countryTable?->id == 1) {
            foreach (CountrySettingKey::all() as $countrySettingKey => $countrySettingLabel) {
                $countrySetting = CountrySetting::where([
                    ['country_id', '=', $company->countryTable->id],
                    ['key', '=', $countrySettingKey],
                ])->first();

                if (!$countrySetting)  return response()->json(['message' => 'Please set Country Setting before submit Run Payroll: ' . $countrySettingKey], 400);
            }
        }

        $runPayroll = app(RunPayrollService::class)->execute($request->validated());

        if (!$runPayroll instanceof RunPayroll && !$runPayroll->getData()?->success) return response()->json($runPayroll->getData(), 400);
        return new RunPayrollResource($runPayroll);
    }

    public function update(UpdateRequest $request, int $id): RunPayrollResource|JsonResponse
    {
        $runPayroll = RunPayroll::findTenanted($id);

        DB::beginTransaction();
        try {
            if (count($request->validated())) {
                $runPayroll->update($request->validated());
                $runPayrollUserComponents = RunPayrollUserComponent::select('id', 'run_payroll_user_id', 'payroll_component_id', 'context')
                    ->whereNotNull('context')
                    ->whereHas('payrollComponent', fn($q) => $q->where('category', \App\Enums\PayrollComponentCategory::LOAN))
                    ->whereHas('runPayrollUser', fn($q) => $q->where('run_payroll_id', $runPayroll->id))
                    ->with('runPayrollUser', fn($q) => $q->select('id'))
                    ->get();

                if ($runPayroll->status->is(\App\Enums\RunPayrollStatus::RELEASE)) {
                    $runPayrollUserComponents->each(function (RunPayrollUserComponent $runPayrollUserComponent) {
                        foreach ($runPayrollUserComponent->context['loans'] ?? [] as $loan) {
                            LoanDetail::whereIn('id', collect($loan['details'])->pluck('id'))->update(['run_payroll_user_id' => $runPayrollUserComponent->runPayrollUser->id]);
                        }
                    });
                } elseif ($runPayroll->status->is(\App\Enums\RunPayrollStatus::REVIEW)) {
                    $runPayrollUserComponents->each(function (RunPayrollUserComponent $runPayrollUserComponent) {
                        foreach ($runPayrollUserComponent->context['loans'] ?? [] as $loan) {
                            LoanDetail::whereIn('id', collect($loan['details'])->pluck('id'))->update(['run_payroll_user_id' => null]);
                        }
                    });
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return (new RunPayrollResource($runPayroll->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }


    public function updateUserComponent(RunPayrollUser $runPayrollUser, UpdateUserComponentRequest $request): RunPayrollResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach ($request->user_components as $userComponent) {
                $runPayrollUserComponent = RunPayrollUserComponent::where('run_payroll_user_id', $runPayrollUser->id)->findOrFail($userComponent['id'], ['id', 'amount']);
                $runPayrollUserComponent->update(['amount' => $userComponent['amount']]);
            }

            RunPayrollService::refreshRunPayrollUser($runPayrollUser);
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new RunPayrollResource($runPayrollUser->runPayroll->refresh()->loadMissing('users.user', 'users.components.payrollComponent')))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id): JsonResponse
    {
        $runPayroll = RunPayroll::findTenanted($id);
        DB::beginTransaction();
        try {
            foreach ($runPayroll->users as $runPayrollUser) {
                $runPayrollUser->components()?->delete();
                $runPayrollUser->delete();
            }

            $runPayroll->delete();

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }

    public function bulkDestroy(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'exists:run_payrolls,id'],
        ]);

        DB::beginTransaction();
        try {
            // ambil semua run_payroll_user_id terkait payroll_id
            $runPayrollUserIds = \App\Models\RunPayrollUser::whereIn('run_payroll_id', $request->ids)->pluck('id');

            if ($runPayrollUserIds->isNotEmpty()) {
                // hapus semua komponennya langsung sekali jalan
                \App\Models\RunPayrollUserComponent::whereIn('run_payroll_user_id', $runPayrollUserIds)->delete();

                // hapus semua run payroll user sekaligus
                \App\Models\RunPayrollUser::whereIn('id', $runPayrollUserIds)->delete();
            }

            // terakhir hapus run payroll
            RunPayroll::whereIn('id', $request->ids)->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function export(int $id)
    {
        $runPayroll = RunPayroll::findTenanted($id);
        $runPayroll->load([
            'users.user' => function ($q) {
                $q->select('id', 'nik', 'name', 'company_id', 'branch_id', 'join_date', 'resign_date')
                    ->with('branch', fn($q) => $q->select('id', 'name'))
                    ->with('payrollInfo', function ($q) {
                        $q->select('user_id', 'bank_id', 'bank_name', 'bank_account_no', 'bank_account_holder', 'secondary_bank_name', 'secondary_bank_account_no', 'secondary_bank_account_holder', 'currency')
                            ->with('bank');
                    })
                    ->with(
                        'positions',
                        fn($q) => $q->select('user_id', 'position_id', 'department_id')
                            ->with('position', fn($q) => $q->select('id', 'name'))
                            ->with('department', fn($q) => $q->select('id', 'name'))
                    );
            },
            'users.components.payrollComponent',
            'company' => fn($q) => $q->select('id', 'name')
        ]);

        return (new RunPayrollExport($runPayroll))->download("payroll $runPayroll->period.xlsx");
    }

    public function exportOcbc(ExportRequest $request, int $id)
    {
        $runPayroll = RunPayroll::select('id', 'code', 'payment_schedule', 'cut_off_end_date', 'payroll_start_date', 'payroll_end_date')->findTenanted($id);
        $bank = Bank::select('id', 'account_no', 'code')->findTenanted($request->bank_id);

        $datas = RunPayrollUser::where('run_payroll_id', $id)
            ->whereHas('user.payrollInfo', fn($q) => $q->where('bank_id', $bank->id))
            ->when($type = $request->type, function ($q) use ($runPayroll, $type) {
                $q->when($type == 'active', fn($q) => $q->whereHas(
                    'user',
                    fn($q) => $q->where(
                        fn($q) =>
                        $q->where('join_date', '<=', $runPayroll->cut_off_end_date)
                            ->whereNull('resign_date')
                    )
                        ->orWhere(
                            fn($q) => $q->whereDate('resign_date', '<=', $runPayroll->cut_off_end_date)
                                ->whereNotNull('resign_date')
                        )
                ))
                    ->when($type == 'resign', fn($q) => $q->whereHas(
                        'user',
                        fn($q) => $q->whereDateBetween('resign_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date)
                    ))
                    ->when($type == 'new', fn($q) => $q->whereHas(
                        'user',
                        fn($q) => $q->whereDateBetween('join_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date)
                    ))
                    ->when($type == 'new_and_resign', fn($q) => $q->whereHas(
                        'user',
                        fn($q) => $q->whereDateBetween('join_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date)->orWhere(fn($q) => $q->whereDateBetween('resign_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date))
                    ));
            })
            ->with([
                'user' => function ($q) {
                    $q->withTrashed()->select('id', 'name')
                        ->with('payrollInfo', fn($q) => $q->select('user_id', 'bank_name', 'bank_account_no', 'bank_account_holder', 'currency'));
                }
            ])
            ->get();

        $body = "";
        foreach ($datas as $runPayrollUser) {
            if (!$runPayrollUser->user) {
                throw new Exception("User with ID $runPayrollUser->user_id not found");
            }

            if (!$runPayrollUser->user->payrollInfo) {
                throw new Exception($runPayrollUser->user->name . "'s payroll info not found");
            }

            if (
                !$runPayrollUser->user->payrollInfo?->bank_account_no ||
                !$runPayrollUser->user->payrollInfo?->bank_account_holder
            ) {
                throw new Exception($runPayrollUser->user->name . "'s bank account not found");
            }

            $body .= PHP_EOL;
            $body .= str_repeat(' ', 20); // 20 O
            $body .= substr(trim($runPayrollUser->user->payrollInfo->bank_account_holder) . str_repeat(' ', 40), 0, 40); // 40 M
            $body .= substr(trim('Jakarta') . str_repeat(' ', 35), 0, 35); // 35 M
            $body .= str_repeat(' ', 35); // 35 O
            $body .= str_repeat(' ', 35); // 35 O
            $body .= 'P'; // 1 M
            $body .= substr(trim($runPayrollUser->user->payrollInfo->bank_account_no) . str_repeat(' ', 34), 0, 34); // 34 M
            $body .= substr(trim($runPayrollUser->user->payrollInfo->currency->value ?? 'IDR') . str_repeat(' ', 3), 0, 3); // 3 M
            $body .= substr(str_repeat('0', 14) . number_format($runPayrollUser->thp, 2, '.', ''), -18, 18); // 18 M(decimal 2)
            $body .= substr(trim($runPayrollUser->user->payrollInfo->currency->value ?? 'IDR') . str_repeat(' ', 3), 0, 3); // 3 M

            $body .= str_repeat(' ', 255); // 255 O
            $body .= str_repeat(' ', 100); // 100 C
            $body .= str_repeat(' ', 50); // 50 C
            $body .= str_repeat(' ', 20); // 20 C
            $body .= str_repeat(' ', 1); // 1 C
            $body .= str_repeat(' ', 1); // 1 C
            $body .= str_repeat(' ', 2); // 2 C
            $body .= str_repeat(' ', 4); // 4 C
            $body .= str_repeat(' ', 2); // 2 C
            $body .= str_repeat(' ', 4); // 4 C
            $body .= str_repeat(' ', 1); // 1 C
            $body .= str_repeat(' ', 5); // 5 C
        }

        $header = [
            'OrgIDVelocity' => substr(trim($bank->code) . str_repeat(' ', 30), 0, 30), // 30 M
            'OrgIDBulk' => substr(trim($bank->code) . str_repeat(' ', 12), 0, 12), // 12 M
            'ProductType' => 'BLIDR', // 5 M
            'ServiceID' => '10001', // 5 M
            'ValueDate' => date('Ymd', strtotime($runPayroll->payment_schedule)), // 8 M
            'DebitAcctCcy' => 'IDR', // 3 M
            'DebitAcctNo' => substr(str_repeat(' ', 19) . trim($bank->account_no), -19, 19), // 19 M
        ];

        $content = implode('', array_values($header)) . $body;

        $fileName = "Payroll $runPayroll->code - OCBC.txt";
        return response($content, 200, [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

    public function exportBca(ExportRequest $request, int $id)
    {
        $runPayroll = RunPayroll::select('id', 'code', 'payment_schedule', 'cut_off_end_date', 'payroll_start_date', 'payroll_end_date')->findTenanted($id);
        $bank = Bank::select('id', 'account_no', 'code')->findTenanted($request->bank_id);

        $datas = RunPayrollUser::where('run_payroll_id', $id)
            ->whereHas('user.payrollInfo', fn($q) => $q->where('bank_id', $bank->id))
            ->when($type = $request->type, function ($q) use ($runPayroll, $type) {
                $q->when($type == 'active', fn($q) => $q->whereHas(
                    'user',
                    fn($q) => $q->where(
                        fn($q) =>
                        $q->where('join_date', '<=', $runPayroll->cut_off_end_date)
                            ->whereNull('resign_date')
                    )
                        ->orWhere(
                            fn($q) => $q->whereDate('resign_date', '<=', $runPayroll->cut_off_end_date)
                                ->whereNotNull('resign_date')
                        )
                ))
                    ->when($type == 'resign', fn($q) => $q->whereHas(
                        'user',
                        fn($q) => $q->whereDateBetween('resign_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date)
                    ))
                    ->when($type == 'new', fn($q) => $q->whereHas(
                        'user',
                        fn($q) => $q->whereDateBetween('join_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date)
                    ))
                    ->when($type == 'new_and_resign', fn($q) => $q->whereHas(
                        'user',
                        fn($q) => $q->whereDateBetween('join_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date)->orWhere(fn($q) => $q->whereDateBetween('resign_date', $runPayroll->payroll_start_date, $runPayroll->payroll_end_date))
                    ));
            })
            ->with([
                'user' => function ($q) {
                    $q->withTrashed()->select('id', 'name', 'nik')
                        ->with('payrollInfo', fn($q) => $q->select('user_id', 'secondary_bank_account_no', 'secondary_bank_account_holder', 'currency'));
                }
            ])
            ->get();

        $body = "";
        $totalAmount = 0;
        foreach ($datas as $runPayrollUser) {
            if (!$runPayrollUser->user) {
                throw new Exception("User with ID $runPayrollUser->user_id not found");
            }

            if (!$runPayrollUser->user->payrollInfo) {
                throw new Exception($runPayrollUser->user->name . "'s payroll info not found");
            }

            if (
                !$runPayrollUser->user->payrollInfo?->secondary_bank_account_no ||
                !$runPayrollUser->user->payrollInfo?->secondary_bank_account_holder
            ) {
                throw new Exception($runPayrollUser->user->name . "'s bank account not found");
            }

            $body .= PHP_EOL;
            $body .= '0'; // 1 default_1
            $body .= substr($runPayrollUser->user->payrollInfo->secondary_bank_account_no, 0, 10); // 10 account_number
            $body .= substr(str_repeat('0', 15) . number_format($runPayrollUser->thp, 2, '', ''), -15, 15); // 15 pay_amount
            $body .= substr($runPayrollUser->user->nik . str_repeat(' ', 10), 0, 10); // 10 nik;
            $body .= substr($runPayrollUser->user->payrollInfo->secondary_bank_account_holder . str_repeat(' ', 30), 0, 30); // 30 name
            $body .= substr('DEP0' . str_repeat(' ', 4), 0, 4); // 4 DEP0
            $totalAmount += $runPayrollUser->thp;
        }

        $totalData = $datas->count();
        $header = [
            'code' => substr(str_repeat(' ', 24) . trim($bank->code), -24, 24), // 24 M
            'day' => date('d', strtotime($runPayroll->payment_schedule)), // 2 M
            'default_1' => '01', // 2 M
            'account_number' => substr(trim($bank->account_no) . str_repeat(' ', 10), 0, 10), // 10 M
            'default_2' => '0000', // 4 M
            'total_data' => substr(str_repeat('0', 5) . $totalData, -5, 5), // 5 M
            'total_amount' => substr(str_repeat('0', 17) . number_format($totalAmount, 2, '.', ''), -17, 17), // 17 M (decimal 2)
            'month' => date('m', strtotime($runPayroll->payment_schedule)), // 2 M
            'year' => date('Y', strtotime($runPayroll->payment_schedule)), // 4 M
        ];

        $header = trim(implode('', array_values($header)));
        $header = substr(str_repeat(' ', 70) . $header, -70, 70);

        $content = $header . $body;

        $fileName = "Payroll $runPayroll->code - BCA.txt";
        return response($content, 200, [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
}
