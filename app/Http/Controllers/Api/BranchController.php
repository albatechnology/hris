<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Branch\StoreRequest;
use App\Http\Requests\Api\Branch\UpdateRequest;
use App\Http\Resources\Branch\BranchResource;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Branch\BranchServiceInterface;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\User;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BranchController extends BaseController
{
    public function __construct(private BranchServiceInterface $service)
    {
        parent::__construct();
        $this->middleware('permission:branch_access', ['only' => ['restore']]);
        $this->middleware('permission:branch_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:branch_create', ['only' => 'store']);
        $this->middleware('permission:branch_edit', ['only' => 'update']);
        $this->middleware('permission:branch_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    private function allowedIncludes(): array
    {
        return ['parent', 'childs'];
    }

    public function index()
    {
        $data = QueryBuilder::for(Branch::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::callback('company_ids', fn($q, $value) => $q->whereIn('company_id', $value)),
                AllowedFilter::scope('is_parent', 'whereIsParent'),
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
            ])
            ->allowedIncludes($this->allowedIncludes())
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
                'created_at',
            ])
            ->allowedFields(['id', 'parent_id', 'name'])
            ->paginate($this->per_page);

        return BranchResource::collection($data);
    }

    public function show(int $id)
    {
        $data = QueryBuilder::for(Branch::tenanted()->where('id', $id))
            ->allowedIncludes($this->allowedIncludes())
            ->firstOrFail();

        return new BranchResource($data);
    }

    public function store(StoreRequest $request)
    {
        $branch = $this->service->create($request->validated());
        return new BranchResource($branch);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $branch = Branch::findTenanted($id);
        $branch->update($request->validated());

        return (new BranchResource($branch))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $branch = Branch::findTenanted($id);
        $branch->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $branch = Branch::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $branch->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $branch = Branch::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $branch->restore();

        return new BranchResource($branch);
    }

    public function summary()
    {
        $branchCount = Branch::tenanted()->count();
        $branchLocationCount = BranchLocation::whereHas('branch', fn($q) => $q->tenanted())->count();
        $userCount = User::tenanted()->whereNull('resign_date')->count();

        $summary = [
            'branch' => $branchCount,
            'branch_location' => $branchLocationCount,
            'active_user' => $userCount,
        ];

        return new DefaultResource($summary);
    }
}
