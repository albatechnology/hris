<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\OvertimeRequest\StoreRequest;
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
        $data = QueryBuilder::for(OvertimeRequest::where('id', $overtimeRequest->id))->firstOrFail();

        return new OvertimeRequestResource($data);
    }

    public function store(StoreRequest $request): OvertimeRequestResource | JsonResponse
    {
        try {
            $overtimeRequest = OvertimeRequest::create([
                'user_id' => $request->user_id,
                'date' => $request->date,
                'shift_id' => $request->shift_id,
                'overtime_id' => $request->overtime_id,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'note' => $request->note,
            ]);
        } catch (\Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new OvertimeRequestResource($overtimeRequest);
    }
}
