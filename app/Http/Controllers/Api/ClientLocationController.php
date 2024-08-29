<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ClientLocation\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Client;
use App\Models\ClientLocation;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientLocationController extends BaseController
{
    private Client $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = Client::tenanted()->where('id', request()->segment(3))->firstOrFail(['id']);

        $this->middleware('permission:client_location_access', ['only' => ['restore']]);
        $this->middleware('permission:client_location_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:client_location_create', ['only' => 'store']);
        $this->middleware('permission:client_location_edit', ['only' => 'update']);
        $this->middleware('permission:client_location_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(int $clientId)
    {
        $data = QueryBuilder::for(ClientLocation::where('client_id', $this->client->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('client_id'),
                'name','address'
            ])
            ->allowedSorts([
                'id', 'client_id', 'name', 'address', 'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $clientId, int $id)
    {
        $clientLocation = $this->client->clientLocations()->findOrFail($id);
        $clientLocation->load('client');

        return new DefaultResource($clientLocation);
    }

    public function store(int $clientId, StoreRequest $request)
    {
       $clientLocation = $this->client->locations()->create($request->validated());

        return new DefaultResource($clientLocation);
    }

    public function update(int $clientId, int $id, StoreRequest $request)
    {
        $clientLocation = $this->client->clientLocations()->findOrFail($id);
        $clientLocation->update($request->validated());

        return (new DefaultResource($clientLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $clientId, int $id)
    {
        $clientLocation = $this->client->clientLocations()->findOrFail($id);
        $clientLocation->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $clientId, $id)
    {
        $clientLocation = $this->client->clientLocations()->withTrashed()->findOrFail($id);
        $clientLocation->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $clientId, $id)
    {
        $clientLocation = $this->client->clientLocations()->withTrashed()->findOrFail($id);
        $clientLocation->restore();

        return new DefaultResource($clientLocation);
    }
}
