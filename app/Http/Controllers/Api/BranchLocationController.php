<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\BranchLocation\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\BranchLocation\BranchLocationServiceInterface;
use App\Models\BranchLocation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BranchLocationController extends BaseController
{
    public function __construct(protected BranchLocationServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return ['branch'];
    }

    public function index()
    {
        Gate::authorize('viewAny', BranchLocation::class);

        $data = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted(),
            [
                AllowedFilter::exact('id'),
                AllowedFilter::exact('branch_id'),
                'name',
                'address',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'branch_id',
                'name',
                'address',
                'created_at',
            ],
            [
                'id',
                'branch_id',
                'name',
                'address',
                'created_at',
            ],
        );

        return DefaultResource::collection($data);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id, load: ['branch']);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', BranchLocation::class);

        try {
            $data = $this->service->create($request->validated());
            $this->generateImage($data);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($data->load('branch'));
    }

    public function update(string $id, StoreRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

            $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function generateQrCode(Request $request)
    {
        throw new BadRequestHttpException("This endpoint is disabled");
        if ($request->id) {
            $datas = BranchLocation::tenanted()->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))->where('id', $request->id)->get(['id', 'uuid']);
        } else {
            $datas = BranchLocation::tenanted()->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))->get(['id', 'uuid']);
        }

        foreach ($datas as $data) {
            $this->generateImage($data);
        }

        return $datas;
    }

    public function generateImage(BranchLocation $data)
    {
        $mediaCollection = MediaCollection::QR_CODE->value;
        $tempDirectory = 'branch_location_qr_codes';
        if (! File::exists(public_path($tempDirectory))) {
            File::makeDirectory(public_path($tempDirectory));
        }

        $qrCode = 'data:image/png;base64,' . base64_encode(QrCode::size(500)->format('png')->margin(1)->generate(implode(';', ['branch_location', $data->uuid])));
        $base64_str = substr($qrCode, strpos($qrCode, ',') + 1);
        $image = base64_decode($base64_str);
        $path = public_path() . '/' . $tempDirectory . '/' . now()->timestamp . '.png';
        file_put_contents($path, $image);

        $data->addMedia($path)->toMediaCollection($mediaCollection);
    }
}
