<?php

namespace App\Http\Controllers\Api;

use App\Enums\CountrySettingKey;
use App\Exports\RunThrExport;
use App\Http\Requests\Api\RunPayroll\ExportRequest;
use App\Http\Requests\Api\RunThr\UpdateUserComponentRequest;
use App\Http\Requests\Api\RunThr\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Bank;
use App\Models\Company;
use App\Models\CountrySetting;
use App\Models\RunThr;
use App\Models\RunThrUser;
use App\Models\RunThrUserComponent;
use App\Services\RunThrService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RunThrController extends BaseController
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
        $data = QueryBuilder::for(RunThr::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'thr_date',
                'payment_date',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'thr_date',
                'payment_date',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id): DefaultResource
    {
        $runThr = RunThr::findTenanted($id);
        return new DefaultResource($runThr->load(['users.user', 'users.components.payrollComponent']));
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

        $runThr = app(RunThrService::class)->execute($request->validated());

        if (!$runThr instanceof RunThr && !$runThr->getData()?->success) return response()->json($runThr->getData(), 400);
        return new DefaultResource($runThr);
    }

    // public function update(int $id, UpdateRequest $request): DefaultResource|JsonResponse
    // {
    // $runThr = RunThr::findTenanted($id);
    //     DB::beginTransaction();
    //     try {
    //         $runThr->update($request->validated());

    //         self::saveRelationship($runThr, $request);

    //         FormulaService::sync($runThr, $request->formulas);

    //         DB::commit();
    //     } catch (Exception $th) {
    //         DB::rollBack();

    //         return $this->errorResponse($th->getMessage());
    //     }

    //     return (new DefaultResource($runThr->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    // }


    public function updateUserComponent(RunThrUser $runThrUser, UpdateUserComponentRequest $request): DefaultResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach ($request->user_components as $userComponent) {
                $runThrUserComponent = RunThrUserComponent::where('run_thr_user_id', $runThrUser->id)->findOrFail($userComponent['id'], ['id', 'amount']);
                $runThrUserComponent->update(['amount' => $userComponent['amount']]);
            }

            RunThrService::refreshRunThrUser($runThrUser);
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new DefaultResource($runThrUser->runThr->refresh()->loadMissing('users.user', 'users.components.payrollComponent')))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id): JsonResponse
    {
        $runThr = RunThr::findTenanted($id);
        DB::beginTransaction();
        try {
            foreach ($runThr->users as $runThrUser) {
                $runThrUser->components()?->delete();
                $runThrUser->delete();
            }

            $runThr->delete();

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }

    public function export(int $id)
    {
        $runThr = RunThr::findTenanted($id);
        $runThr->load([
            'users.user' => function ($q) {
                $q->select('id', 'nik', 'name', 'company_id', 'branch_id', 'join_date', 'resign_date')
                    ->with('branch', fn($q) => $q->select('id', 'name'))
                    ->with('payrollInfo', function ($q) {
                        $q->select('user_id', 'bank_id', 'bank_name', 'bank_account_no', 'bank_account_holder', 'secondary_bank_name', 'secondary_bank_account_no', 'secondary_bank_account_holder', 'currency', 'ptkp_status')
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

        return (new RunThrExport($runThr))->download("THR " . date('d-m-Y', strtotime($runThr->thr_date)) . ".xlsx");
    }

    public function exportOcbc(ExportRequest $request, int $id)
    {
        $runThr = RunThr::select('id', 'code', 'payment_date')->findTenanted($id);
        $bank = Bank::select('id', 'account_no', 'code')->findTenanted($request->bank_id);

        $datas = RunThrUser::where('run_thr_id', $id)
            ->whereHas('user.payrollInfo', fn($q) => $q->where('bank_id', $bank->id))
            ->with([
                'user' => function ($q) {
                    $q->withTrashed()->select('id', 'name', 'join_date')
                        ->with('payrollInfo', fn($q) => $q->select('user_id', 'bank_name', 'bank_account_no', 'bank_account_holder', 'currency', 'ptkp_status'));
                }
            ])
            ->get();


        $body = "";
        $totalAmount = 0;
        foreach ($datas as $runThrUser) {
            if (!$runThrUser->user) {
                throw new Exception("User with ID $runThrUser->user_id not found");
            }

            if (!$runThrUser->user->payrollInfo) {
                throw new Exception($runThrUser->user->full_name . "'s payroll info not found");
            }

            if (
                !$runThrUser->user->payrollInfo?->bank_account_no ||
                !$runThrUser->user->payrollInfo?->bank_account_holder
            ) {
                throw new Exception($runThrUser->user->full_name . "'s bank account not found");
            }

            $body .= PHP_EOL;
            $body .= str_repeat(' ', 20); // 20 O
            $body .= substr(trim($runThrUser->user->payrollInfo->bank_account_holder) . str_repeat(' ', 40), 0, 40); // 40 M
            $body .= substr(trim('Jakarta') . str_repeat(' ', 35), 0, 35); // 35 M
            $body .= str_repeat(' ', 35); // 35 O
            $body .= str_repeat(' ', 35); // 35 O
            $body .= 'P'; // 1 M
            $body .= substr(trim($runThrUser->user->payrollInfo->bank_account_no) . str_repeat(' ', 34), 0, 34); // 34 M
            $body .= substr(trim($runThrUser->user->payrollInfo->currency->value ?? 'IDR') . str_repeat(' ', 3), 0, 3); // 3 M
            $body .= substr(str_repeat('0', 14) . number_format($runThrUser->thp_thr, 2, '.', ''), -18, 18); // 18 M(decimal 2)
            $body .= substr(trim($runThrUser->user->payrollInfo->currency->value ?? 'IDR') . str_repeat(' ', 3), 0, 3); // 3 M

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
            'ValueDate' => date('Ymd', strtotime($runThr->payment_date)), // 8 M
            'DebitAcctCcy' => 'IDR', // 3 M
            'DebitAcctNo' => substr(str_repeat(' ', 19) . trim($bank->account_no), -19, 19), // 19 M
        ];

        $content = implode('', array_values($header)) . $body;

        $fileName = "THR $runThr->code - OCBC.txt";
        return response($content, 200, [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

    public function exportBca(ExportRequest $request, int $id)
    {
        $runThr = RunThr::select('id', 'code', 'payment_date')->findTenanted($id);
        $bank = Bank::select('id', 'account_no', 'code')->findTenanted($request->bank_id);

        $datas = RunThrUser::where('run_thr_id', $id)
            ->whereHas('user.payrollInfo', fn($q) => $q->where('bank_id', $bank->id))
            ->with([
                'user' => function ($q) {
                    $q->withTrashed()->select('id', 'name', 'join_date', 'nik')
                        ->with('payrollInfo', fn($q) => $q->select('user_id', 'secondary_bank_account_no', 'secondary_bank_account_holder', 'currency', 'ptkp_status'));
                }
            ])
            ->get();

        $body = "";
        $totalAmount = 0;
        foreach ($datas as $runThrUser) {
            if (!$runThrUser->user) {
                throw new Exception("User with ID $runThrUser->user_id not found");
            }

            if (!$runThrUser->user->payrollInfo) {
                throw new Exception($runThrUser->user->full_name . "'s payroll info not found");
            }

            if (
                !$runThrUser->user->payrollInfo?->secondary_bank_account_no ||
                !$runThrUser->user->payrollInfo?->secondary_bank_account_holder
            ) {
                throw new Exception($runThrUser->user->full_name . "'s bank account not found");
            }

            $body .= PHP_EOL;
            $body .= '0'; // 1 default_1
            $body .= substr($runThrUser->user->payrollInfo->secondary_bank_account_no, 0, 10); // 10 account_number
            $body .= substr(str_repeat('0', 15) . number_format($runThrUser->thp_thr, 2, '', ''), -15, 15); // 15 pay_amount
            $body .= substr($runThrUser->user->nik . str_repeat(' ', 10), 0, 10); // 10 nik;
            $body .= substr($runThrUser->user->payrollInfo->secondary_bank_account_holder . str_repeat(' ', 30), 0, 30); // 30 name
            $body .= substr('DEP0' . str_repeat(' ', 4), 0, 4); // 4 DEP0
            $totalAmount += $runThrUser->thp_thr;
        }

        $totalData = $datas->count();
        $header = [
            'code' => substr(str_repeat(' ', 24) . trim($bank->code), -24, 24), // 24 M
            'day' => date('d', strtotime($runThr->payment_date)), // 2 M
            'default_1' => '01', // 2 M
            'account_number' => substr(trim($bank->account_no) . str_repeat(' ', 10), 0, 10), // 10 M
            'default_2' => '0000', // 4 M
            'total_data' => substr(str_repeat('0', 5) . $totalData, -5, 5), // 5 M
            'total_amount' => substr(str_repeat('0', 17) . number_format($totalAmount, 2, '.', ''), -17, 17), // 17 M (decimal 2)
            'month' => date('m', strtotime($runThr->payment_date)), // 2 M
            'year' => date('Y', strtotime($runThr->payment_date)), // 4 M
        ];

        $header = trim(implode('', array_values($header)));
        $header = substr(str_repeat(' ', 70) . $header, -70, 70);

        $content = $header . $body;

        $fileName = "THR $runThr->code - BCA.txt";
        return response($content, 200, [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
}
