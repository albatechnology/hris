<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Group\StoreRequest;
use App\Http\Resources\Group\GroupResource;
use App\Models\Group;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
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
                AllowedFilter::exact('id'),
                'name',
            ])
            ->allowedSorts([
                'id', 'name', 'created_at',
            ])
            ->paginate($this->per_page);

        return GroupResource::collection($data);
    }

    public function show(Group $group)
    {
        return new GroupResource($group);
    }

    public function store(StoreRequest $request)
    {
        $group = Group::create($request->validated());

        return new GroupResource($group);
    }

    public function update(Group $group, StoreRequest $request)
    {
        $group->update($request->validated());

        return (new GroupResource($group))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $group = Group::withTrashed()->findOrFail($id);
        $group->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $group = Group::withTrashed()->findOrFail($id);
        $group->restore();

        return new GroupResource($group);
    }
}
