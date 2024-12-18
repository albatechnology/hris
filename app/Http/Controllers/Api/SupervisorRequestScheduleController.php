<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\ApproveRequest;
use App\Http\Requests\Api\Schedule\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\Schedule\ScheduleResource;
use App\Models\Schedule;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SupervisorRequestScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:supervisor_request_schedule_access', ['only' => ['restore']]);
        $this->middleware('permission:supervisor_request_schedule_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:supervisor_request_schedule_create', ['only' => 'store']);
        $this->middleware('permission:supervisor_request_schedule_delete', ['only' => ['destroy', 'forceDelete']]);
        $this->middleware('permission:supervisor_request_schedule_edit', ['only' => 'update']);
        $this->middleware('permission:schedule_edit', ['only' => 'approve']);
    }

    public function index()
    {
        $data = QueryBuilder::for(Schedule::requestTenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('created_by'),
                AllowedFilter::exact('approved_by'),
                'name',
                'type',
                'effective_date',
                'approval_status',
                'approved_at',
            ])
            ->allowedIncludes(['company', 'created_by', 'approved_by'])
            ->allowedSorts([
                'id',
                'company_id',
                'created_by',
                'approved_by',
                'name',
                'effective_date',
                'approval_status',
                'created_at',
                'approved_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $requestSchedule = Schedule::requestTenanted()->where('id', $id)->firstOrFail();
        $requestSchedule->load([
            'shifts' => fn($q) => $q->orderBy('order'),
            'created_by' => fn($q) => $q->select('id', 'name', 'last_name'),
            'approved_by' => fn($q) => $q->select('id', 'name', 'last_name'),
        ]);
        return new DefaultResource($requestSchedule);
    }

    public function store(StoreRequest $request)
    {
        $validated = $request->safe()->merge([
            'is_need_approval' => true,
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        DB::beginTransaction();
        try {
            $schedule = Schedule::create($validated->input());

            $order = 1;
            foreach ($request->shifts ?? [] as $shift) {
                $schedule->shifts()->attach($shift['id'], ['order' => $order++]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new ScheduleResource($schedule);
    }
    public function update(int $id, StoreRequest $request)
    {
        $schedule = Schedule::requestTenanted()->where('id', $id)->firstOrFail();
        DB::beginTransaction();
        try {
            $schedule->shifts()->sync([]);
            $schedule->update($request->validated());

            $order = 1;
            foreach ($request->shifts ?? [] as $shift) {
                $schedule->shifts()->attach($shift['id'], ['order' => $order++]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return (new ScheduleResource($schedule))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $schedule = Schedule::requestTenanted()->where('id', $id)->firstOrFail();
        $schedule->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $schedule = Schedule::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $schedule->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $schedule = Schedule::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $schedule->restore();

        return new ScheduleResource($schedule);
    }

    public function approve(ApproveRequest $request, int $id)
    {
        $schedule = Schedule::requestTenanted()->where('id', $id)->firstOrFail();
        DB::beginTransaction();
        try {
            $schedule->update($request->validated());
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }
}
