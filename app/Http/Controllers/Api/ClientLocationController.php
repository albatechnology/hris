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
use Illuminate\Http\Request;
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

        $this->middleware('permission:client_access', ['only' => ['restore']]);
        $this->middleware('permission:client_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:client_create', ['only' => 'store']);
        $this->middleware('permission:client_edit', ['only' => 'update']);
        $this->middleware('permission:client_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(ClientLocation::whereHas('client', function ($q) {
            $q->tenanted();
        })->with('client'))->allowedFilters([
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

    public function show(ClientLocation $clientLocation)
    {
        return new DefaultResource($clientLocation->load('client'));
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

        return new DefaultResource($clientLocation->load('client'));
    }

    public function update(ClientLocation $clientLocation, StoreRequest $request)
    {
        try {
            $clientLocation->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($clientLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(ClientLocation $clientLocation)
    {
        try {
            $clientLocation->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function generateQrCode(Request $request)
    {
        if ($request->id) {
            $clientLocations = ClientLocation::where('id', $request->id)->get();
        } else {
            $clientLocations = ClientLocation::all();
        }

        foreach ($clientLocations as $clientLocation) {
            $clientLocation->clearMediaCollection(MediaCollection::QR_CODE->value);

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
        }

        return $clientLocations;
    }
}
