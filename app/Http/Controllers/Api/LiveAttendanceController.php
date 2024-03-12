<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LiveAttendance\StoreRequest;
use App\Http\Resources\LiveAttendance\LiveAttendanceResource;
use App\Http\Resources\User\UserResource;
use App\Models\LiveAttendance;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LiveAttendanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:live_attendance_access', ['only' => ['restore']]);
        $this->middleware('permission:live_attendance_read', ['only' => ['index', 'show', 'users']]);
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
                'is_flexible',
            ])
            ->allowedIncludes(['company', 'locations'])
            ->allowedSorts([
                'id', 'name', 'is_flexible', 'created_at',
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
        DB::beginTransaction();
        try {
            $liveAttendance = LiveAttendance::create($request->validated());

            if ($request->locations && count($request->locations) > 0) {
                $liveAttendance->locations()->createMany($request->locations);
            }

            if ($request->user_ids && count($request->user_ids) > 0) {
                User::whereIn('id', $request->user_ids)->update(['live_attendance_id' => $liveAttendance->id]);
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
        DB::beginTransaction();
        try {
            $liveAttendance->update($request->validated());

            if ($liveAttendance->is_flexible) {
                $liveAttendance->locations()->delete();
            }

            if ($request->user_ids && count($request->user_ids) > 0) {
                User::whereIn('id', $request->user_ids)->update(['live_attendance_id' => $liveAttendance->id]);
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new LiveAttendanceResource($liveAttendance))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(LiveAttendance $liveAttendance)
    {
        DB::beginTransaction();
        try {
            $liveAttendance->locations()->delete();
            $liveAttendance->delete();
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

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

    public function users(LiveAttendance $liveAttendance)
    {
        $query = User::select('id', 'name', 'nik', 'branch_id', 'company_id', 'live_attendance_id')
            ->tenanted()->where('live_attendance_id', $liveAttendance->id)
            ->with([
                'company' => fn ($q) => $q->select('id', 'name'),
                'branch' => fn ($q) => $q->select('id', 'name'),
                'detail' => fn ($q) => $q->select('id', 'job_position'),
                'liveAttendance'
            ]);

        $users = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('company_id'),
                'name', 'email', 'nik', 'phone',
            ])
            ->allowedSorts([
                'id', 'company_id', 'branch_id', 'manager_id', 'name', 'email', 'nik', 'phone', 'created_at',
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }
}
