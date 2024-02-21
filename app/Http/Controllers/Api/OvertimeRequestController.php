<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\OvertimeRequest\StoreRequest;
use App\Http\Requests\Api\OvertimeRequest\UpdateStatusRequest;
use App\Http\Resources\OvertimeRequest\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OvertimeRequestController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:overtime_request_access', ['only' => ['restore']]);
        $this->middleware('permission:overtime_request_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:overtime_request_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(OvertimeRequest::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date'
            ])
            ->paginate($this->per_page);

        return OvertimeRequestResource::collection($data);
    }

    public function show(OvertimeRequest $overtimeRequest): OvertimeRequestResource
    {
        return new OvertimeRequestResource($overtimeRequest);
    }

    public function store(StoreRequest $request): OvertimeRequestResource | JsonResponse
    {
        try {
            $overtimeRequest = OvertimeRequest::create($request->validated());
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }

    public function updateStatus(UpdateStatusRequest $request, OvertimeRequest $overtimeRequest): OvertimeRequestResource | JsonResponse
    {
        try {
            $overtimeRequest->update([
                'status' => $request->status,
            ]);
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }
}
