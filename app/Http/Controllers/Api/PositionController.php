<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Position\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Department;
use App\Models\Position;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class PositionController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:position_access', ['only' => ['restore']]);
        $this->middleware('permission:position_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:position_create', ['only' => 'store']);
        $this->middleware('permission:position_edit', ['only' => 'update']);
        $this->middleware('permission:position_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    private function getAllowedIncludes()
    {
        return [
            AllowedInclude::callback('user', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('company', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('department', function ($query) {
                $query->select('id', 'name');
            }),
        ];
    }

    public function index()
    {
        $datas = QueryBuilder::for(Position::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('department_id'),
                'name',
            ])
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedSorts([
                'id',
                'user_id',
                'company_id',
                'department_id',
                'name',
                'order',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = QueryBuilder::for(Position::tenanted()->where('id', $id))
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        $data = Position::create($request->validated());

        return new DefaultResource($data);
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = Position::findTenanted($id);
        $companyId = Department::findTenanted($data->department_id)->company_id;

        $updatedData = $request->validated();
        $updatedData['company_id'] = $companyId;
        $data->update($updatedData);

        return new DefaultResource($data);
    }

    public function destroy(int $id)
    {
        $data = Position::findTenanted($id);
        $data->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $data = Position::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $data->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $data = Position::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $data->restore();

        return new DefaultResource($data);
    }
}
