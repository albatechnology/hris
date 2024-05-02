<?php

namespace App\Http\Controllers\Api;

use App\Enums\PayrollComponentCategory;
use App\Http\Requests\Api\RunPayroll\StoreRequest;
use App\Http\Requests\Api\RunPayroll\UpdateRequest;
use App\Http\Requests\Api\RunPayroll\UserSettingRequest;
use App\Http\Resources\RunPayroll\RunPayrollResource;
use App\Models\PayrollComponent;
use App\Models\RunPayroll;
use App\Models\UpdatePayrollComponent;
use App\Models\User;
use App\Services\FormulaService;
use App\Services\RunPayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('period'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'period', 'payment_schedule', 'created_at',
            ])
            ->paginate($this->per_page);

        return RunPayrollResource::collection($data);
    }

    public function show(RunPayroll $runPayroll): RunPayrollResource
    {
        return new RunPayrollResource($runPayroll->load(['users.user', 'users.components.payrollComponent']));
    }

    public function store(StoreRequest $request): RunPayrollResource|JsonResponse
    {
        $runPayroll = app(RunPayrollService::class)->execute($request->validated());
        return new RunPayrollResource($runPayroll->refresh()->loadMissing('users.user', 'users.components.payrollComponent'));
    }

    // public function update(RunPayroll $runPayroll, UpdateRequest $request): RunPayrollResource|JsonResponse
    // {
    //     DB::beginTransaction();
    //     try {
    //         $runPayroll->update($request->validated());

    //         self::saveRelationship($runPayroll, $request);

    //         FormulaService::sync($runPayroll, $request->formulas);

    //         DB::commit();
    //     } catch (\Exception $th) {
    //         DB::rollBack();

    //         return $this->errorResponse($th->getMessage());
    //     }

    //     return (new RunPayrollResource($runPayroll->refresh()))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    // }

    public function destroy(RunPayroll $runPayroll): JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach ($runPayroll->users as $runPayrollUser) {
                $runPayrollUser->components()?->delete();
                $runPayrollUser->delete();
            }

            $runPayroll->delete();

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
