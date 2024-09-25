<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\ClientLocation\ScanClientLocationRequest;
use App\Http\Requests\Api\ClientLocation\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Client;
use App\Models\ClientLocation;
use BadMethodCallException;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ClientLocationController extends BaseController
{
    private ?Client $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = Client::tenanted()->where('id', request()->filter['client_id'] ?? null)->first();

        if (!$this->client) {
            throw ValidationException::withMessages(['client_id' => ['Invalid Client ID.']]);
        }

        $this->middleware('permission:client_access', ['only' => ['restore']]);
        $this->middleware('permission:client_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:client_create', ['only' => 'store']);
        $this->middleware('permission:client_edit', ['only' => 'update']);
        $this->middleware('permission:client_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(ClientLocation::where('client_id', $this->client->id))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('client_id'),
                'name',
                'address'
            ])
            ->allowedSorts([
                'id',
                'client_id',
                'name',
                'address',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $clientLocation = ClientLocation::findOrFail($id);
        $clientLocation->load('client');

        return new DefaultResource($clientLocation);
    }

    public function store(StoreRequest $request)
    {
        try {
            $clientLocation = ClientLocation::create($request->validated());

            $mediaCollections = [MediaCollection::QR_CODE->value];
            $tempDirectory = 'client_location_qr_codes';
            if (! File::exists(public_path($tempDirectory))) {
                File::makeDirectory(public_path($tempDirectory));
            }
            foreach ($mediaCollections as $mediaCollection) {
                $qrCode = 'data:image/png;base64,' . base64_encode(QrCode::size(500)->format('png')->margin(1)->generate(implode(';', ['client_location', $clientLocation->uuid])));
                $base64_str = substr($qrCode, strpos($qrCode, ',') + 1);
                $image = base64_decode($base64_str);
                $path = public_path() . '/' . $tempDirectory . '/' . now()->timestamp . '.png';
                file_put_contents($path, $image);

                $clientLocation->addMedia($path)->toMediaCollection($mediaCollection);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($clientLocation);
    }

    public function update(int $id, StoreRequest $request)
    {
        $clientLocation = ClientLocation::findOrFail($id);

        try {
            $clientLocation->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($clientLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $clientLocation = ClientLocation::findOrFail($id);

        try {
            $clientLocation->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $clientLocation = ClientLocation::withTrashed()->findOrFail($id);

        try {
            $clientLocation->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $clientLocation = ClientLocation::withTrashed()->findOrFail($id);

        try {
            $clientLocation->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($clientLocation);
    }
    
    public function scanQrCode(ScanClientLocationRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        $splittedToken = explode(';', $request->token);
        $type = $splittedToken[0] ?? null;
        $uuid = $splittedToken[1] ?? null;

        $clientLocation = ClientLocation::firstWhere('uuid', $uuid);

        if(!$clientLocation){
            return $this->errorResponse('Invalid token');
        }

        $clientLocation->load('client');

        return new DefaultResource($clientLocation);
    }
}
