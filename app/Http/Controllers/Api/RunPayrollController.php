<?php

namespace App\Http\Controllers\Api;

use App\Enums\CountrySettingKey;
use App\Exports\RunPayrollExport;
use App\Http\Requests\Api\RunPayroll\UpdateUserComponentRequest;
use App\Http\Requests\Api\RunPayroll\StoreRequest;
use App\Http\Resources\RunPayroll\RunPayrollResource;
use App\Models\Company;
use App\Models\CountrySetting;
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
                AllowedFilter::exact('period'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
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
        return new RunPayrollResource($runPayroll->refresh()->loadMissing('users.user', 'users.components.payrollComponent'));
    }

    // public function update(int $id, UpdateRequest $request): RunPayrollResource|JsonResponse
    // {
    // $runPayroll = RunPayroll::findTenanted($id);
    //     DB::beginTransaction();
    //     try {
    //         $runPayroll->update($request->validated());

    //         self::saveRelationship($runPayroll, $request);

    //         FormulaService::sync($runPayroll, $request->formulas);

    //         DB::commit();
    //     } catch (Exception $th) {
    //         DB::rollBack();

    //         return $this->errorResponse($th->getMessage());
    //     }

    //     return (new RunPayrollResource($runPayroll->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    // }


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

    public function export(int $id)
    {
        $runPayroll = RunPayroll::findTenanted($id);
        $runPayroll->load([
            'users.user' => function ($q) {
                $q->select('id', 'nik', 'name', 'last_name', 'company_id', 'branch_id', 'join_date', 'resign_date')
                    ->with('branch', fn($q) => $q->select('id', 'name'))
                    ->with('positions', fn($q) => $q->select('user_id', 'position_id')
                        ->with('position', fn($q) => $q->select('id', 'name')));
            },
            'users.components.payrollComponent',
            'company' => fn($q) => $q->select('id', 'name')
        ]);

        return (new RunPayrollExport($runPayroll))->download("payroll $runPayroll->period .xlsx");
    }

    public function exportOcbc(int $id)
    {
        $runPayroll = RunPayroll::select('id', 'code')->findTenanted($id);
        $datas = RunPayrollUser::where('run_payroll_id', $id)
            ->with([
                'user' => function ($q) {
                    $q->withTrashed()->select('id', 'name', 'last_name')
                        ->with('payrollInfo', fn($q) => $q->select('user_id', 'bank_name', 'bank_account_no', 'bank_account_holder', 'currency'));
                }
            ])
            ->get()
            ->map(function (RunPayrollUser $runPayrollUser) {
                if (!$runPayrollUser->user) {
                    throw new Exception("User with ID $runPayrollUser->user_id not found");
                }

                if (!$runPayrollUser->user->payrollInfo) {
                    throw new Exception($runPayrollUser->user->full_name . "'s payroll info not found");
                }

                return [
                    'PayeeID' => str_repeat(' ', 20), // 20 O
                    'PayeeName' => substr(trim($runPayrollUser->user->payrollInfo->bank_account_holder) . str_repeat(' ', 40), 0, 40), // 40 M
                    'PayeeAddr1' => substr(trim('Jakarta') . str_repeat(' ', 35), 0, 35), // 35 M
                    'PayeeAddr2' => str_repeat(' ', 35), // 35 O
                    'PayeeAddr3' => str_repeat(' ', 35), // 35 O
                    'PaymentType' => 'P', // 1 M
                    'PayeeAccountNo' => substr(trim($runPayrollUser->user->payrollInfo->bank_account_no) . str_repeat(' ', 34), 0, 34), // 34 M
                    'PayeeAccountCcy' => substr(trim($runPayrollUser->user->payrollInfo->currency->value ?? 'IDR') . str_repeat(' ', 3), 0, 3), // 3 M
                    'PayAmount' => substr(str_repeat('0', 14) . number_format($runPayrollUser->thp, 2, '.', ''), -18, 18), // 18 M (decimal 2)
                    'PayCcy' => substr(trim($runPayrollUser->user->payrollInfo->currency->value ?? 'IDR') . str_repeat(' ', 3), 0, 3), // 3 M
                    'Remarks' => str_repeat(' ', 255), // 255 O
                    'BeneBankName' => str_repeat(' ', 100), // 100 C
                    'BeneBankBranchName' => str_repeat(' ', 50), // 50 C
                    'BeneBankNetworkID' => str_repeat(' ', 20), // 20 C
                    'ReservedColumn1' => str_repeat(' ', 1), // 1 C
                    'ResidentStatus' => str_repeat(' ', 1), // 1 C
                    'ReservedColumn2' => str_repeat(' ', 2), // 2 C
                    'RemitterCategory' => str_repeat(' ', 4), // 4 C
                    'ReservedColumn3' => str_repeat(' ', 2), // 2 C
                ];
            });

        $header = [
            'OrgIDVelocity' => substr(trim('SUNEDUCATION') . str_repeat(' ', 30), 0, 30), // 30 M
            'OrgIDBulk' => substr(trim('SUNEDUCATION') . str_repeat(' ', 40), 0, 40), // 12 M
            'ProductType' => 'BLIDR', // 5 M
            'ServiceID' => '10001', // 5 M
            'ValueDate' => date('Ymd'), // 8 M
            'DebitAcctCcy' => 'IDR', // 3 M
            'DebitAcctNo' => '625800011136', // 19 M
            'DebitAcctNo' => substr(trim('625800011136') . str_repeat(' ', 19), 0, 19), // 30 M
        ];

        $content = implode('', array_values($header)) . PHP_EOL;
        foreach ($datas as $data) {
            $content .= $data['PayeeID'];
            $content .= $data['PayeeName'];
            $content .= $data['PayeeAddr1'];
            $content .= $data['PayeeAddr2'];
            $content .= $data['PayeeAddr3'];
            $content .= $data['PaymentType'];
            $content .= $data['PayeeAccountNo'];
            $content .= $data['PayeeAccountCcy'];
            $content .= $data['PayAmount'];
            $content .= $data['PayCcy'];
            $content .= $data['Remarks'];
            $content .= $data['BeneBankName'];
            $content .= $data['BeneBankBranchName'];
            $content .= $data['BeneBankNetworkID'];
            $content .= $data['ReservedColumn1'];
            $content .= $data['ResidentStatus'];
            $content .= $data['ReservedColumn2'];
            $content .= $data['RemitterCategory'];
            $content .= $data['ReservedColumn3'] . PHP_EOL;
        }
        $fileName = "Payroll $runPayroll->code.txt";
        return response($content, 200, [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }
}
