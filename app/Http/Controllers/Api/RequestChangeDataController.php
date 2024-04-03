<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Enums\RequestChangeDataType;
use App\Http\Requests\Api\RequestChangeData\ApproveRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RequestChangeData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;
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
        $data = QueryBuilder::for(RequestChangeData::tenanted()->where('user_id', auth('sanctum')->id()))
            ->allowedIncludes('details')
            ->allowedSorts('id')
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(RequestChangeData $requestChangeData): DefaultResource
    {
        return new DefaultResource($requestChangeData->load([
            'details',
            'user' => fn ($q) => $q->select('id', 'name'),
            'approvedBy' => fn ($q) => $q->select('id', 'name'),
        ]));
    }

    public function approve(ApproveRequest $request, RequestChangeData $requestChangeData): DefaultResource|JsonResponse
    {
        if (!is_null($requestChangeData->is_approved)) {
            return $this->errorResponse(message: 'Status can not be changed', code: \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $requestChangeData->update($request->validated());
            if ($requestChangeData->is_approved === true) {
                $userId = $requestChangeData->user_id;
                $requestChangeData->details->each(fn ($detail) => RequestChangeDataType::updateData($detail->type, $userId, $detail->value));
            }

            $notificationType = NotificationType::REQUEST_CHANGE_DATA_APPROVED;
            $requestChangeData->user->notify(new ($notificationType->getNotificationClass())($notificationType, $requestChangeData->approvedBy, $requestChangeData->is_approved, $requestChangeData));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }

    public function approvals()
    {
        $query = RequestChangeData::tenanted()->whereHas('user', fn ($q) => $q->where('parent_id', auth('sanctum')->id()));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
            ])
            ->allowedIncludes('details')
            ->allowedSorts([
                'id', 'user_id',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
