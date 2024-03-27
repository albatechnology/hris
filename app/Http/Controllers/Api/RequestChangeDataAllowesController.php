<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RequestChangeDataAllowes\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RequestChangeDataAllowes;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RequestChangeDataAllowesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:request_change_data_allowes_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:request_change_data_allowes_create', ['only' => 'store']);
        $this->middleware('permission:request_change_data_allowes_edit', ['only' => 'update']);
        $this->middleware('permission:request_change_data_allowes_delete', ['only' => 'destroy']);
    }

    public function index()
    {
        $data = QueryBuilder::for(RequestChangeDataAllowes::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                'type',
            ])
            ->allowedSorts([
                'id', 'type', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(RequestChangeDataAllowes $requestChangeDataAllowes)
    {
        return new DefaultResource($requestChangeDataAllowes);
    }

    public function store(StoreRequest $request)
    {
        $requestChangeDataAllowes = RequestChangeDataAllowes::create($request->validated());

        return new DefaultResource($requestChangeDataAllowes);
    }

    public function update(RequestChangeDataAllowes $requestChangeDataAllowes, StoreRequest $request)
    {
        $requestChangeDataAllowes->update($request->validated());

        return (new DefaultResource($requestChangeDataAllowes))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(RequestChangeDataAllowes $requestChangeDataAllowes)
    {
        $requestChangeDataAllowes->delete();

        return $this->deletedResponse();
    }
}
