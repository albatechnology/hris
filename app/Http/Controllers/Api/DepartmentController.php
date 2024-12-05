<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Department\StoreRequest;
use App\Http\Resources\Department\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:department_access', ['only' => ['restore']]);
        $this->middleware('permission:department_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:department_create', ['only' => 'store']);
        $this->middleware('permission:department_edit', ['only' => 'update']);
        $this->middleware('permission:department_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Department::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('division_id'),
                AllowedFilter::scope('company_id'),
                'name',
            ])
            ->allowedIncludes(['division'])
            ->allowedSorts([
                'id',
                'division_id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DepartmentResource::collection($data);
    }

    public function show(int $id)
    {
        $department = Department::findTenanted($id);
        return new DepartmentResource($department);
    }

    public function store(StoreRequest $request)
    {
        $department = Department::create($request->validated());

        return new DepartmentResource($department);
    }

    public function update(int $id, StoreRequest $request)
    {
        $department = Department::findTenanted($id);
        $department->update($request->validated());

        return (new DepartmentResource($department))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $department = Department::findTenanted($id);
        $department->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $department = Department::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $department->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $department = Department::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $department->restore();

        return new DepartmentResource($department);
    }
}
