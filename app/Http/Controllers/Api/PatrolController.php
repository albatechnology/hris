<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Patrol\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Patrol;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatrolController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Patrol::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('client_id'),
                'name','start_date','end_date','start_time','end_time'
            ])
            ->allowedSorts([
                'id', 'client_id', 'name','start_date','end_date','start_time','end_time', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Patrol $patrol)
    {
        $patrol->load('client');
        return new DefaultResource($patrol);
    }

    public function store(StoreRequest $request)
    {
        $patrol = Patrol::create($request->validated());

        return new DefaultResource($patrol);
    }

    public function update(Patrol $patrol, StoreRequest $request)
    {
        $patrol->update($request->validated());

        return (new DefaultResource($patrol))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Patrol $patrol)
    {
        $patrol->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $patrol = Patrol::withTrashed()->findOrFail($id);
        $patrol->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $patrol = Patrol::withTrashed()->findOrFail($id);
        $patrol->restore();

        return new DefaultResource($patrol);
    }
}
