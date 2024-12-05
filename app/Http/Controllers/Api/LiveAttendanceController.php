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
                'name',
                'is_flexible',
            ])
            ->allowedIncludes(['company', 'locations', 'users'])
            ->allowedSorts([
                'id',
                'name',
                'is_flexible',
                'created_at',
            ])
            ->paginate($this->per_page);

        return LiveAttendanceResource::collection($data);
    }

    public function show(int $id)
    {
        $liveAttendance = LiveAttendance::findTenanted($id);
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

    public function update(int $id, StoreRequest $request)
    {
        $liveAttendance = LiveAttendance::findTenanted($id);
        DB::beginTransaction();
        try {
            $liveAttendance->update($request->validated());

            $liveAttendance->locations()->delete();
            if (!$liveAttendance->is_flexible) {
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

        return (new LiveAttendanceResource($liveAttendance))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $liveAttendance = LiveAttendance::findTenanted($id);
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

    public function forceDelete(int $id)
    {
        $liveAttendance = LiveAttendance::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $liveAttendance->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $liveAttendance = LiveAttendance::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $liveAttendance->restore();

        return new LiveAttendanceResource($liveAttendance);
    }

    public function users()
    {
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
