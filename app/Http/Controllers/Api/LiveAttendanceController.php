<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LiveAttendance\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\User\UserResource;
use App\Interfaces\Services\LiveAttendance\LiveAttendanceServiceInterface;
use App\Models\LiveAttendance;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LiveAttendanceController extends BaseController
{
    public function __construct(private LiveAttendanceServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', LiveAttendance::class);

        $data = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name',
                'is_flexible',
            ],
            ['company', 'locations', 'users'],
            [
                'id',
                'name',
                'is_flexible',
                'created_at',
            ],
        );

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data->load(['company', 'locations']));
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', LiveAttendance::class);

        $this->service->createWithRelations(
            $request->validated(),
            $request->locations ?? [],
            $request->user_ids ?? [],
        );

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->updateWithRelations(
            $id,
            $request->validated(),
            $request->locations ?? [],
            $request->user_ids ?? [],
        );

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function users()
    {
        Gate::authorize('viewAny', LiveAttendance::class);

        $query = User::select('id', 'name', 'nik', 'branch_id', 'company_id', 'live_attendance_id')
            ->tenanted()
            ->with([
                'company' => fn($q) => $q->select('id', 'name'),
                'branch' => fn($q) => $q->select('id', 'name'),
                'detail' => fn($q) => $q->select('id', 'job_position'),
            ]);

        $users = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('live_attendance_id'),
                'name',
                'email',
                'nik',
            ])
            ->allowedIncludes([
                \Spatie\QueryBuilder\AllowedInclude::callback('liveAttendance', fn($q) => $q->select('id', 'name')),
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'branch_id',
                'live_attendance_id',
                'name',
                'email',
                'nik',
                'created_at',
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }
}
