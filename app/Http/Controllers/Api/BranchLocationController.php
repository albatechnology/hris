<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\BranchLocation\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\BranchLocation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BranchLocationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:branch_access', ['only' => ['restore']]);
        $this->middleware('permission:branch_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:branch_create', ['only' => 'store']);
        $this->middleware('permission:branch_edit', ['only' => 'update']);
        $this->middleware('permission:branch_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(
            BranchLocation::tenanted()->with('branch')
        )->allowedFilters([
            AllowedFilter::exact('id'),
            AllowedFilter::exact('branch_id'),
            'name',
            'address'
        ])
            ->allowedSorts([
                'id',
                'branch_id',
                'name',
                'address',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $branchLocation = BranchLocation::findTenanted($id);
        return new DefaultResource($branchLocation->load('Branch'));
    }

    public function store(StoreRequest $request)
    {
        try {
            $branchLocation = BranchLocation::create($request->validated());

            $this->generateImage($branchLocation);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($branchLocation->load('Branch'));
    }

    public function update(int $id, StoreRequest $request)
    {
        $branchLocation = BranchLocation::findTenanted($id);

        try {
            $branchLocation->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($branchLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $branchLocation = BranchLocation::findTenanted($id);

        try {
            $branchLocation->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function generateQrCode(Request $request)
    {
        throw new BadRequestHttpException("This endpoint is disabled");
        if ($request->id) {
            $branchLocations = BranchLocation::tenanted()->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))->where('id', $request->id)->get(['id', 'uuid']);
        } else {
            $branchLocations = BranchLocation::tenanted()->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))->get(['id', 'uuid']);
        }

        foreach ($branchLocations as $branchLocation) {
            $this->generateImage($branchLocation);
        }

        return $branchLocations;
    }

    public function generateImage(BranchLocation $branchLocation)
    {
        $mediaCollection = MediaCollection::QR_CODE->value;
        $tempDirectory = 'branch_location_qr_codes';
        if (! File::exists(public_path($tempDirectory))) {
            File::makeDirectory(public_path($tempDirectory));
        }

        $qrCode = 'data:image/png;base64,' . base64_encode(QrCode::size(500)->format('png')->margin(1)->generate(implode(';', ['branch_location', $branchLocation->uuid])));
        $base64_str = substr($qrCode, strpos($qrCode, ',') + 1);
        $image = base64_decode($base64_str);
        $path = public_path() . '/' . $tempDirectory . '/' . now()->timestamp . '.png';
        file_put_contents($path, $image);

        $branchLocation->addMedia($path)->toMediaCollection($mediaCollection);
    }
}
