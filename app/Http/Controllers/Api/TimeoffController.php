<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\MediaCollection;
use App\Http\Requests\Api\Timeoff\ApproveRequest;
use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Http\Resources\Timeoff\TimeoffResource;
use App\Models\Timeoff;
use App\Services\ScheduleService;
use App\Services\TimeoffService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:timeoff_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_edit', ['only' => ['update', 'approve']]);
        $this->middleware('permission:timeoff_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Timeoff::tenanted()->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')))->with('media'))
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('timeoff_policy_id'),
                AllowedFilter::exact('delegate_to'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'request_type',
            ])
            ->allowedIncludes(['user', 'timeoffPolicy', 'delegateTo'])
            ->allowedSorts([
                'id',
                'user_id',
                'timeoff_policy_id',
                'delegate_to',
                'start_at',
                'end_at',
                'request_type',
                'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }

    public function show(int $id)
    {
        $timeoff = Timeoff::findTenanted($id);
        $timeoff->load(['user', 'timeoffPolicy', 'delegateTo', 'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))]);

        return new TimeoffResource($timeoff);
    }

    public function store(StoreRequest $request)
    {
        $request = TimeoffService::requestTimeoffValidation($request);

        DB::beginTransaction();
        try {
            $timeoff = Timeoff::create($request->all());

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        $timeoff->addMedia($file)->toMediaCollection(MediaCollection::TIMEOFF->value);
                    }
                }
            }

            // $notificationType = NotificationType::REQUEST_TIMEOFF;
            // $timeoff->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $timeoff->user, $timeoff));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(message: $e->getMessage());
        }

        return $this->createdResponse();
        // return new TimeoffResource($timeoff);
    }

    public function update(int $id, StoreRequest $request)
    {
        $timeoff = Timeoff::findTenanted($id);
        if (!ScheduleService::checkAvailableSchedule(startDate: $request->start_at, endDate: $request->end_at)) {
            return $this->errorResponse(message: 'Schedule is not available', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $timeoff->update($request->validated());

        return (new TimeoffResource($timeoff))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $timeoff = Timeoff::findTenanted($id);
        $timeoff->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $timeoff = Timeoff::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $timeoff->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $timeoff = Timeoff::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $timeoff->restore();

        return new TimeoffResource($timeoff);
    }

    public function approve(Timeoff $timeoff, ApproveRequest $request)
    {
        $requestApproval = $timeoff->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$timeoff->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        // if ($timeoff->approval_status == ApprovalStatus::APPROVED->value && in_array($request->approval_status, [ApprovalStatus::APPROVED->value, ApprovalStatus::REJECTED->value])) {

        if ($timeoff->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // $timeoff->approval_status = $request->approval_status;
        // if (!$timeoff->isDirty('approval_status')) {
        if ($timeoff->approval_status == $request->approval_status) {
            return $this->errorResponse('Nothing to update', [], Response::HTTP_BAD_REQUEST);
        }

        $startSchedule = ScheduleService::getTodaySchedule($timeoff->user, $timeoff->start_at);
        $endSchedule = ScheduleService::getTodaySchedule($timeoff->user, $timeoff->end_at);

        if (!$startSchedule && !$endSchedule) {
            return $this->errorResponse(message: sprintf('There is a schedule that cannot be found between %s and %s', $timeoff->start_at, $timeoff->end_at), code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!in_array($request->approval_status, [ApprovalStatus::PENDING->value, ApprovalStatus::REJECTED->value])) {
            $remainingBalance = TimeoffService::getTotalBalanceQuota($timeoff->user_id, $timeoff->timeoff_policy_id);
            if ($remainingBalance <= 0 || $remainingBalance < $timeoff->total_days) {
                return $this->errorResponse(message: 'User Leave balance is not enough', code: Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // if (!in_array($request->approval_status, [ApprovalStatus::PENDING->value, ApprovalStatus::REJECTED->value])) {
        //     // untuk history timeoff
        //     $value = 0.5;
        //     if ($timeoff->request_type->is(TimeoffRequestType::FULL_DAY)) {
        //         $startDate = new \DateTime($timeoff->start_at);
        //         $endDate = new \DateTime($timeoff->end_at);
        //         if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
        //             $value = 1;
        //         } else {
        //             $interval = $startDate->diff($endDate);
        //             $value = $interval->days;
        //         }
        //     }

        //     if ($value > $timeoff->user->total_timeoff) {
        //         return response()->json(['message' => 'Leave request exceeds leave quota.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        //     }
        // }

        DB::beginTransaction();
        try {
            $requestApproval->update($request->validated());



            // if (!$timeoff->approval_status->is(ApprovalStatus::PENDING)) {
            //     $notificationType = NotificationType::TIMEOFF_APPROVED;
            //     $timeoff->user?->notify(new ($notificationType->getNotificationClass())($notificationType, $timeoff->user->approval, $timeoff->approval_status, $timeoff));
            // }
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = Timeoff::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = Timeoff::myApprovals()
            ->with([
                'user' => fn($q) => $q->select('id', 'name'),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
            ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('timeoff_policy_id'),
                AllowedFilter::exact('delegate_to'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'request_type',
            ])
            ->allowedIncludes(['timeoffPolicy', 'delegateTo'])
            ->allowedSorts([
                'id',
                'user_id',
                'timeoff_policy_id',
                'delegate_to',
                'start_at',
                'end_at',
                'request_type',
                'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }
}
