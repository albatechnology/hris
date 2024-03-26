<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Requests\Api\RequestChangeData\StoreRequest;
use App\Http\Requests\Api\RequestChangeData\ApproveRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RequestChangeData;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RequestChangeDataController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:request_change_data_access', ['only' => ['restore']]);
        $this->middleware('permission:request_change_data_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:request_change_data_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(RequestChangeData::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(RequestChangeData $requestChangeData): DefaultResource
    {
        return new DefaultResource($requestChangeData);
    }

    public function store(StoreRequest $request): DefaultResource|JsonResponse
    {
        try {
            $requestChangeData = RequestChangeData::create($request->validated());

            $notificationType = NotificationType::REQUEST_OVERTIME;
            $requestChangeData->user->manager?->notify(new ($notificationType->getNotificationClass())($notificationType, $requestChangeData->user, $requestChangeData));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($requestChangeData);
    }

    public function approve(ApproveRequest $request, RequestChangeData $requestChangeData): DefaultResource|JsonResponse
    {
        try {
            $requestChangeData->update($request->validated());

            $notificationType = NotificationType::OVERTIME_APPROVED;
            $requestChangeData->user->notify(new ($notificationType->getNotificationClass())($notificationType, $requestChangeData->approvedBy, $requestChangeData->is_approved, $requestChangeData));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($requestChangeData);
    }

    public function approvals()
    {
        $query = RequestChangeData::whereHas('user', fn ($q) => $q->where('manager_id', auth('sanctum')->id()));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
