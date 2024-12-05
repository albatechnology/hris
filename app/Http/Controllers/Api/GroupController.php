<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Group\StoreRequest;
use App\Http\Resources\Group\GroupResource;
use App\Models\Group;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;

class GroupController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:group_access', ['only' => ['restore']]);
        $this->middleware('permission:group_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:group_create', ['only' => 'store']);
        $this->middleware('permission:group_edit', ['only' => 'update']);
        $this->middleware('permission:group_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Group::tenanted())
            ->allowedFilters([
                'name',
            ])
            ->allowedSorts([
                'id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return GroupResource::collection($data);
    }

    public function show(int $id)
    {
        $group = Group::findTenanted($id);
        return new GroupResource($group);
    }

    public function store(StoreRequest $request)
    {
        $group = Group::create($request->validated());

        return new GroupResource($group);
    }

    public function update(int $id, StoreRequest $request)
    {
        $group = Group::findTenanted($id);
        $group->update($request->validated());

        return (new GroupResource($group))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $group = Group::findTenanted($id);
        $group->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $group = Group::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $group->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $group = Group::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $group->restore();

        return new GroupResource($group);
    }
}
