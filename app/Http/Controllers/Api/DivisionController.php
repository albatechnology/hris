<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Division\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Division;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class DivisionController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:division_access', ['only' => ['restore']]);
        $this->middleware('permission:division_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:division_create', ['only' => 'store']);
        $this->middleware('permission:division_edit', ['only' => 'update']);
        $this->middleware('permission:division_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    private function getAllowedIncludes()
    {
        return [
            AllowedInclude::callback('company', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('user', function ($query) {
                $query->select('id', 'name');
            }),
        ];
    }

    public function index()
    {
        $datas = QueryBuilder::for(Division::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('user_id'),
                'name',
            ])
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedSorts([
                'id',
                'company_id',
                'user_id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = QueryBuilder::for(Division::tenanted()->where('id', $id))
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        $data = Division::create($request->validated());

        return new DefaultResource($data);
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = Division::findTenanted($id);
        $data->update($request->validated());

        return new DefaultResource($data);
    }

    public function destroy(int $id)
    {
        $data = Division::findTenanted($id);
        $data->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $data = Division::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $data->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $data = Division::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $data->restore();

        return new DefaultResource($data);
    }
}
