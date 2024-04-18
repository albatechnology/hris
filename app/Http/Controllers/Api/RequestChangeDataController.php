<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
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
            ->allowedFilters([
                'approval_status'
            ])
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
        if (!$requestChangeData->approval_status->is(ApprovalStatus::PENDING)) {
            return $this->errorResponse(message: 'Status can not be changed', code: \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $requestChangeData->update($request->validated());
            if ($requestChangeData->approval_status->is(ApprovalStatus::APPROVED)) {
                $requestChangeData->load(['user' => fn ($q) => $q->select('id')]);
                $user = $requestChangeData->user;

                $requestChangeData->details->each(function (\App\Models\RequestChangeDataDetail $detail) use ($user) {
                    if ($detail->type->is(RequestChangeDataType::PHOTO_PROFILE)) {
                        $detail->getFirstMedia(\App\Enums\MediaCollection::REQUEST_CHANGE_DATA->value)->copy($user, \App\Enums\MediaCollection::USER->value);
                    } else {
                        RequestChangeDataType::updateData($detail->type, $user->id, $detail->value);
                    }
                });
            }

            $notificationType = NotificationType::REQUEST_CHANGE_DATA_APPROVED;
            $requestChangeData->user->notify(new ($notificationType->getNotificationClass())($notificationType, $requestChangeData->approvedBy, $requestChangeData->approval_status, $requestChangeData));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = RequestChangeData::where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = RequestChangeData::tenanted()->whereHas('user', fn ($q) => $q->where('approval_id', auth('sanctum')->id()))
            ->with('user', fn ($q) => $q->select('id', 'name'));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                'approval_status'
            ])
            ->allowedIncludes('details')
            ->allowedSorts([
                'id', 'user_id',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
