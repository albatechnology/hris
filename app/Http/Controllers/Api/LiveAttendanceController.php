<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LiveAttendance\StoreRequest;
use App\Http\Resources\LiveAttendance\LiveAttendanceLocationResource;
use App\Http\Resources\LiveAttendance\LiveAttendanceResource;
use App\Models\LiveAttendance;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LiveAttendanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:live_attendance_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:live_attendance_access', ['only' => ['restore']]);
        $this->middleware('permission:live_attendance_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:live_attendance_create', ['only' => 'store']);
        $this->middleware('permission:live_attendance_edit', ['only' => 'update']);
        $this->middleware('permission:live_attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(LiveAttendance::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'name',
                'is_flexible'
            ])
            ->allowedIncludes(['company', 'locations'])
            ->allowedSorts([
                'id', 'name', 'is_flexible', 'created_at'
            ])
            ->paginate($this->per_page);

        return LiveAttendanceResource::collection($data);
    }

    public function show(LiveAttendance $liveAttendance)
    {
        return new LiveAttendanceResource($liveAttendance->load(['company', 'locations']));
    }

    public function store(StoreRequest $request)
    {
        dd($request->validated());
        DB::beginTransaction();
        try {
            $liveAttendance = LiveAttendance::create($request->validated());

            if ($request->locations && count($request->locations) > 0) {
                $liveAttendance->locations()->createMany($request->locations);
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new LiveAttendanceResource($liveAttendance);
    }

    public function update(LiveAttendance $liveAttendance, StoreRequest $request)
    {
        $liveAttendance->update($request->validated());

        return (new LiveAttendanceResource($liveAttendance))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(LiveAttendance $liveAttendance)
    {
        $liveAttendance->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $liveAttendance = LiveAttendance::withTrashed()->findOrFail($id);
        $liveAttendance->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $liveAttendance = LiveAttendance::withTrashed()->findOrFail($id);
        $liveAttendance->restore();
        return new LiveAttendanceResource($liveAttendance);
    }

    public function locations(LiveAttendance $liveAttendance)
    {
        return LiveAttendanceLocationResource::collection($liveAttendance->locations);
    }
}
