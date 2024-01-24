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
        // $this->middleware('permission:department_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:department_access', ['only' => ['restore']]);
        $this->middleware('permission:department_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:department_create', ['only' => 'store']);
        $this->middleware('permission:department_edit', ['only' => 'update']);
        $this->middleware('permission:department_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Department::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('division_id'),
                'name'
            ])
            ->allowedSorts([
                'id', 'division_id', 'name', 'created_at'
            ])
            ->paginate($this->per_page);

        return DepartmentResource::collection($data);
    }

    public function show(Department $Department)
    {
        return new DepartmentResource($Department);
    }

    public function store(StoreRequest $request)
    {
        $Department = Department::create($request->validated());

        return new DepartmentResource($Department);
    }

    public function update(Department $Department, StoreRequest $request)
    {
        $Department->update($request->validated());

        return (new DepartmentResource($Department))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Department $Department)
    {
        $Department->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $Department = Department::withTrashed()->findOrFail($id);
        $Department->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $Department = Department::withTrashed()->findOrFail($id);
        $Department->restore();
        return new DepartmentResource($Department);
    }
}
