<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Department\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Department;
use App\Models\Division;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
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

    private function getAllowedIncludes()
    {
        return [
            AllowedInclude::callback('company', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('division', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('user', function ($query) {
                $query->select('id', 'name');
            }),
        ];
    }

    public function index()
    {
        $data = QueryBuilder::for(Department::tenanted())
            ->allowedFilters([
                AllowedFilter::scope('company_id'),
                AllowedFilter::exact('division_id'),
                AllowedFilter::exact('user_id'),
                'name',
            ])
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedSorts([
                'id',
                'company_id',
                'division_id',
                'user_id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $data = QueryBuilder::for(Department::tenanted()->where('id', $id))
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        $companyId = Division::findTenanted($request->division_id)->company_id;
        if (!$companyId) {
            return $this->errorResponse('Division ID is required');
        }

        $updatedData = $request->validated();
        $updatedData['company_id'] = $companyId;

        $data = Department::create($updatedData);

        return new DefaultResource($data);
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = Department::findTenanted($id);

        $divisionId = $request->division_id ?? $data->division_id;
        if (!$divisionId) {
            return $this->errorResponse('Division ID is required');
        }

        $companyId = Division::findTenanted($divisionId)->company_id;

        $updatedData = $request->validated();
        $updatedData['company_id'] = $companyId;
        $data->update($updatedData);

        return new DefaultResource($data);
    }

    public function destroy(int $id)
    {
        $data = Department::findTenanted($id);
        $data->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $data = Department::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $data->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $data = Department::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $data->restore();

        return new DefaultResource($data);
    }
}
