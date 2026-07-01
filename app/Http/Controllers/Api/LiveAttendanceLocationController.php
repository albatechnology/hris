<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LiveAttendanceLocation\StoreRequest;
use App\Http\Requests\Api\LiveAttendanceLocation\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\LiveAttendanceLocation\LiveAttendanceLocationServiceInterface;
use App\Models\LiveAttendanceLocation;
use Illuminate\Support\Facades\Gate;

class LiveAttendanceLocationController extends BaseController
{
    public function __construct(private LiveAttendanceLocationServiceInterface $service)
    {
        parent::__construct();
    }

    public function index(string $id)
    {
        Gate::authorize('viewAny', LiveAttendanceLocation::class);

        $data = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->where('live_attendance_id', $id),
            [
                'radius',
                'lat',
                'lng',
            ],
            ['liveAttendance'],
            [
                'id',
                'radius',
                'lat',
                'lng',
                'created_at',
            ],
        );

        return DefaultResource::collection($data);
    }

    public function show(string $liveAttendanceId, string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->where('live_attendance_id', $liveAttendanceId));
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(string $id, StoreRequest $request)
    {
        Gate::authorize('create', LiveAttendanceLocation::class);

        $this->service->createMany($id, $request->locations ?? []);

        return $this->createdResponse();
    }

    public function update(string $liveAttendanceId, string $id, UpdateRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id')->where('live_attendance_id', $liveAttendanceId));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $liveAttendanceId, string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id')->where('live_attendance_id', $liveAttendanceId));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }
}
