<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LockAttendance\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\LockAttendance;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LockAttendanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:lock_attendance_access', ['only' => ['restore']]);
        // $this->middleware('permission:lock_attendance_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:lock_attendance_create', ['only' => 'store']);
        // $this->middleware('permission:lock_attendance_edit', ['only' => 'update']);
        // $this->middleware('permission:lock_attendance_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(LockAttendance::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'start_date',
                'end_date',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'start_date',
                'end_date',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $lockAttendance = LockAttendance::findTenanted($id);
        return new DefaultResource($lockAttendance);
    }

    public function store(StoreRequest $request)
    {
        $lockAttendance = LockAttendance::create($request->validated());

        return new DefaultResource($lockAttendance);
    }

    public function update(int $id, StoreRequest $request)
    {
        $lockAttendance = LockAttendance::findTenanted($id);
        $lockAttendance->update($request->validated());

        return (new DefaultResource($lockAttendance))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $lockAttendance = LockAttendance::findTenanted($id);
        $lockAttendance->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $lockAttendance = LockAttendance::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $lockAttendance->forceDelete();

        return $this->deletedResponse();
    }
}
