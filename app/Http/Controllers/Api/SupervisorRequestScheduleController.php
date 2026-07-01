<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ApproveRequest;
use App\Http\Requests\Api\Schedule\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\SupervisorRequestSchedule\SupervisorRequestScheduleServiceInterface;
use Spatie\QueryBuilder\AllowedFilter;

class SupervisorRequestScheduleController extends BaseController
{
    public function __construct(private SupervisorRequestScheduleServiceInterface $service)
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
        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->requestTenanted(),
            [
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('created_by_id'),
                AllowedFilter::exact('approved_by'),
                'name',
                'type',
                'effective_date',
                'approval_status',
                'approved_at',
            ],
            ['company', 'created_by_id', 'approved_by'],
            [
                'id',
                'company_id',
                'created_by_id',
                'approved_by',
                'name',
                'effective_date',
                'approval_status',
                'created_at',
                'approved_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $requestSchedule = $this->service->findById($id, null, [
            'shifts' => fn($q) => $q->orderBy('order'),
            'created_by_id' => fn($q) => $q->select('id', 'name'),
            'approved_by' => fn($q) => $q->select('id', 'name'),
        ]);

        return new DefaultResource($requestSchedule);
    }

    public function store(StoreRequest $request)
    {
        $schedule = $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request)
    {
        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $schedule = $this->service->findById($id);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $schedule = $this->service->findById($id, fn($q) => $q->withTrashed());

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(int $id)
    {
        $schedule = $this->service->findById($id, fn($q) => $q->withTrashed());

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function approve(ApproveRequest $request, int $id)
    {
        $this->service->approve($id, $request->validated());

        return $this->updatedResponse("Schedule approved successfully.");
    }
}
