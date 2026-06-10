<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\JobPosition\StoreRequest;
use App\Http\Requests\Api\JobPosition\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\JobPositionServiceInterface;
use App\Models\JobPosition;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JobPositionController extends BaseController
{
    public function __construct(protected JobPositionServiceInterface $service)
    {
        parent::__construct();
        // $this->middleware('permission:job_position_access', ['only' => ['restore']]);
        // $this->middleware('permission:job_position_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:job_position_create', ['only' => 'store']);
        // $this->middleware('permission:job_position_edit', ['only' => 'update']);
        // $this->middleware('permission:job_position_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function chartView(int $companyId)
    {
        $positions = JobPosition::tenanted()
            ->where('company_id', $companyId)
            ->with('users:id,name,job_position_id')
            ->get();

        $grouped = $positions->groupBy('parent_id');

        $buildTree = function ($parentId) use ($grouped, &$buildTree) {
            $nodes = $grouped->get($parentId, collect());
            return $nodes->values()->map(fn($node) => [
                'id' => $node->id,
                'name' => $node->name,
                'code' => $node->code,
                'parent_id' => $node->parent_id,
                'users' => $node->users->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    // 'email' => $user->email,
                ]),
                'children' => $buildTree($node->id),
            ]);
        };

        return response()->json(['data' => $buildTree(null)]);
    }

    public function index()
    {
        $datas = QueryBuilder::for(JobPosition::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('parent_id'),
                'name',
                'code',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'parent_id',
                'name',
                'code',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        $data = $this->service->create($request->validated());

        return new DefaultResource($data);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $this->service->findById($id);
        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $this->service->findById($id);
        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $this->service->forceDelete($id);

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $this->service->restore($id);

        return $this->okResponse();
    }
}
