<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Client\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Client;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:client_access', ['only' => ['restore']]);
        $this->middleware('permission:client_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:client_create', ['only' => 'store']);
        $this->middleware('permission:client_edit', ['only' => 'update']);
        $this->middleware('permission:client_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Client::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name','phone','address'
            ])
            ->allowedSorts([
                'id', 'company_id', 'name', 'phone', 'address', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Client $client)
    {
        $client->load('company');
        return new DefaultResource($client);
    }

    public function store(StoreRequest $request)
    {
        $client = Client::create($request->validated());

        return new DefaultResource($client);
    }

    public function update(Client $client, StoreRequest $request)
    {
        $client->update($request->validated());

        return (new DefaultResource($client))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        $client->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        $client->restore();

        return new DefaultResource($client);
    }
}
