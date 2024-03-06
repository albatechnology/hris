<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LiveAttendanceLocation\StoreRequest;
use App\Http\Requests\Api\LiveAttendanceLocation\UpdateRequest;
use App\Http\Resources\LiveAttendance\LiveAttendanceLocationResource;
use App\Models\LiveAttendance;
use App\Models\LiveAttendanceLocation;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
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

    public function index(LiveAttendance $liveAttendance)
    {
        $data = QueryBuilder::for(LiveAttendanceLocation::where('live_attendance_id', $liveAttendance->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'radius',
                'lat',
                'lng',
            ])
            ->allowedIncludes(['liveAttendance'])
            ->allowedSorts([
                'id', 'radius', 'lat', 'lng', 'created_at',
            ])
            ->paginate($this->per_page);

        return LiveAttendanceLocationResource::collection($data);
    }

    public function show(LiveAttendance $liveAttendance, LiveAttendanceLocation $location)
    {
        return new LiveAttendanceLocationResource($location);
    }

    public function store(LiveAttendance $liveAttendance, StoreRequest $request)
    {
        $liveAttendance->locations()->createMany($request->locations);

        return $this->createdResponse();
    }

    public function update(LiveAttendance $liveAttendance, LiveAttendanceLocation $location, UpdateRequest $request)
    {
        $location->update($request->validated());

        return (new LiveAttendanceLocationResource($location))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(LiveAttendance $liveAttendance, LiveAttendanceLocation $location)
    {
        $liveAttendance->locations()->findOrFail($location->id)->delete();

        return $this->deletedResponse();
    }
}
