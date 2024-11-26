<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Position\StoreRequest;
use App\Http\Resources\Position\PositionResource;
use App\Models\Position;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
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

    public function index()
    {
        $data = QueryBuilder::for(Position::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'order', 'created_at',
            ])
            ->paginate($this->per_page);

        return PositionResource::collection($data);
    }

    public function show(Position $position)
    {
        return new PositionResource($position);
    }

    public function store(StoreRequest $request)
    {
        $position = Position::create($request->validated());

        return new PositionResource($position);
    }

    public function update(Position $position, StoreRequest $request)
    {
        $position->update($request->validated());

        return (new PositionResource($position))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $position = Position::withTrashed()->findOrFail($id);
        $position->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $position = Position::withTrashed()->findOrFail($id);
        $position->restore();

        return new PositionResource($position);
    }
}
