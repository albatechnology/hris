<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Client\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\ClientLocation;
use App\Models\User;
use Exception;
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
                'name',
                'phone',
                'address'
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'phone',
                'address',
                'created_at',
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
        try {
            $client = Client::create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($client);
    }

    public function update(Client $client, StoreRequest $request)
    {
        try {
            $client->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($client))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $client = Client::withTrashed()->findOrFail($id);

        try {
            $client->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $client = Client::withTrashed()->findOrFail($id);

        try {
            $client->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($client);
    }

    public function summary()
    {
        $clientCount = Client::tenanted()->count();
        $clientLocationCount = ClientLocation::whereHas('client', fn($q) => $q->tenanted())->count();
        $userCount = User::tenanted()->count();
        $clockInCount = Attendance::whereHas('schedule', fn($q) => $q->tenanted())->whereHas('details', fn($q) => $q->where('is_clock_in', true)->whereDate('time', now()))->count();
        $clockOutCount = Attendance::whereHas('schedule', fn($q) => $q->tenanted())->whereHas('details', fn($q) => $q->where('is_clock_in', true)->whereDate('time', now()))->count();

        $summary = [
            'client' => $clientCount,
            'client_location' => $clientLocationCount,
            'active_user' => $userCount,
            'clock_in' => $clockInCount,
            'clock_out' => $clockOutCount,
        ];

        return new DefaultResource($summary);
    }
}
