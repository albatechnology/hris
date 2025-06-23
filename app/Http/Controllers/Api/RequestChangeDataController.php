<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Requests\ApprovalStatusRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RequestChangeData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
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
        // $this->middleware('permission:request_change_data_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(RequestChangeData::tenanted()->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))))
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                // 'approval_status'
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
            'user' => fn($q) => $q->select('id', 'name'),
            // 'approvedBy' => fn($q) => $q->select('id', 'name'),
        ]));
    }

    public function approve(NewApproveRequest $request, RequestChangeData $requestChangeData): DefaultResource|JsonResponse
    {
        $requestApproval = $requestChangeData->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$requestChangeData->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($requestChangeData->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $requestApproval->update($request->validated());

            // $notificationType = NotificationType::REQUEST_CHANGE_DATA_APPROVED;
            // $requestChangeData->user->notify(new ($notificationType->getNotificationClass())($notificationType, auth()->user(), $requestApproval->approval_status, $requestChangeData));
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }

    public function countTotalApprovals(ApprovalStatusRequest $request)
    {
        $total = RequestChangeData::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])
            ->when($request->branch_id, fn($q) => $q->whereBranch($request->branch_id))
            ->when($request->name, fn($q) => $q->whereUserName($request->name))
            ->when($request->created_at, fn($q) => $q->createdAt($request->created_at))
            ->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = RequestChangeData::myApprovals()
            ->with('user', fn($q) => $q->select('id', 'name'))
            ->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                AllowedFilter::scope('branch_id', 'whereBranch'),
                AllowedFilter::scope('name', 'whereUserName'),
                'created_at',
            ])
            ->allowedIncludes('details')
            ->allowedSorts([
                'id',
                'user_id',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    // public function approvals()
    // {
    //     $query = RequestChangeData::tenanted()
    //         ->whereHas('user', fn($q) => $q->where('approval_id', auth('sanctum')->id()))
    //         ->with('user', fn($q) => $q->select('id', 'name'));

    //     $data = QueryBuilder::for($query)
    //         ->allowedFilters([
    //             AllowedFilter::exact('user_id'),
    //             'approval_status'
    //         ])
    //         ->allowedIncludes('details')
    //         ->allowedSorts([
    //             'id',
    //             'user_id',
    //         ])
    //         ->paginate($this->per_page);

    //     return DefaultResource::collection($data);
    // }
}
