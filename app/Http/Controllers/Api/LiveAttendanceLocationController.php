<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LiveAttendanceLocation\StoreRequest;
use App\Http\Requests\Api\LiveAttendanceLocation\UpdateRequest;
use App\Http\Resources\LiveAttendance\LiveAttendanceLocationResource;
use App\Models\LiveAttendance;
use App\Models\LiveAttendanceLocation;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;

class LiveAttendanceLocationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:live_attendance_access', ['only' => ['restore']]);
        $this->middleware('permission:live_attendance_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:live_attendance_create', ['only' => 'store']);
        $this->middleware('permission:live_attendance_edit', ['only' => 'update']);
        $this->middleware('permission:live_attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(int $id)
    {
        $liveAttendance = LiveAttendance::findTenanted($id);
        $data = QueryBuilder::for(LiveAttendanceLocation::where('live_attendance_id', $liveAttendance->id))
            ->allowedFilters([
                'radius',
                'lat',
                'lng',
            ])
            ->allowedIncludes(['liveAttendance'])
            ->allowedSorts([
                'id',
                'radius',
                'lat',
                'lng',
                'created_at',
            ])
            ->paginate($this->per_page);

        return LiveAttendanceLocationResource::collection($data);
    }

    public function show(int $liveAttendanceId, int $id)
    {
        $liveAttendanceLocation = LiveAttendanceLocation::findTenanted($id);
        return new LiveAttendanceLocationResource($liveAttendanceLocation);
    }

    public function store(int $id, StoreRequest $request)
    {
        $liveAttendance = LiveAttendance::findTenanted($id);
        $liveAttendance->locations()->createMany($request->locations);

        return $this->createdResponse();
    }

    public function update(int $liveAttendanceId, int $id, UpdateRequest $request)
    {
        $liveAttendanceLocation = LiveAttendanceLocation::findTenanted($id);
        $liveAttendanceLocation->update($request->validated());

        return (new LiveAttendanceLocationResource($liveAttendanceLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $liveAttendanceId, int $id)
    {
        $liveAttendance = LiveAttendance::findTenanted($liveAttendanceId);
        $liveAttendance->locations()->findOrFail($id)->delete();

        return $this->deletedResponse();
    }
}
