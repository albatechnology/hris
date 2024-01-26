<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Branch\StoreRequest;
use App\Http\Requests\Api\Branch\UpdateRequest;
use App\Http\Resources\Branch\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BranchController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:branch_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:branch_access', ['only' => ['restore']]);
        $this->middleware('permission:branch_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:branch_create', ['only' => 'store']);
        $this->middleware('permission:branch_edit', ['only' => 'update']);
        $this->middleware('permission:branch_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Branch::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name', 'country', 'province', 'city', 'zip_code', 'address'
            ])
            ->allowedSorts([
                'id', 'company_id', 'name', 'country', 'province', 'city', 'zip_code', 'address', 'created_at'
            ])
            ->paginate($this->per_page);

        return BranchResource::collection($data);
    }

    public function show(Branch $branch)
    {
        return new BranchResource($branch);
    }

    public function store(StoreRequest $request)
    {
        $branch = Branch::create($request->validated());

        return new BranchResource($branch);
    }

    public function update(Branch $branch, UpdateRequest $request)
    {
        $branch->update($request->validated());

        return (new BranchResource($branch))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $branch = Branch::withTrashed()->findOrFail($id);
        $branch->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $branch = Branch::withTrashed()->findOrFail($id);
        $branch->restore();
        return new BranchResource($branch);
    }
}
