<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Division\StoreRequest;
use App\Http\Resources\Division\DivisionResource;
use App\Models\Division;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DivisionController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:division_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:division_access', ['only' => ['restore']]);
        $this->middleware('permission:division_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:division_create', ['only' => 'store']);
        $this->middleware('permission:division_edit', ['only' => 'update']);
        $this->middleware('permission:division_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Division::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'created_at',
            ])
            ->paginate($this->per_page);

        return DivisionResource::collection($data);
    }

    public function show(Division $division)
    {
        return new DivisionResource($division);
    }

    public function store(StoreRequest $request)
    {
        $division = Division::create($request->validated());

        return new DivisionResource($division);
    }

    public function update(Division $division, StoreRequest $request)
    {
        $division->update($request->validated());

        return (new DivisionResource($division))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Division $division)
    {
        $division->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $division = Division::withTrashed()->findOrFail($id);
        $division->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $division = Division::withTrashed()->findOrFail($id);
        $division->restore();

        return new DivisionResource($division);
    }
}
