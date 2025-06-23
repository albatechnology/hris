<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\MediaCollection;
use App\Enums\TimeoffPolicyType;
use App\Http\Requests\Api\Timeoff\ApproveRequest;
use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Http\Resources\Timeoff\TimeoffResource;
use App\Models\Timeoff;
use App\Models\TimeoffQuota;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use App\Services\TimeoffService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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
                AllowedFilter::exact('cancelled_by'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'request_type',
                'is_cancelled',
            ])
            ->allowedIncludes([
                'user',
                'cancelledBy',
                AllowedInclude::callback('timeoffPolicy', fn($query) => $query->selectMinimalist()),
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'timeoff_policy_id',
                'start_at',
                'end_at',
                'request_type',
                'is_cancelled',
                'cancelled_by',
                'cancelled_at',
                'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }

    public function show(int $id)
    {
        $timeoff = Timeoff::findTenanted($id);
        $timeoff->load([
            'user',
            'timeoffPolicy' => fn($query) => $query->selectMinimalist(),
            'cancelledBy',
            'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
        ]);

        return new TimeoffResource($timeoff);
    }

    public function store(StoreRequest $request)
    {
        $user = User::select('id', 'company_id')->where('id', $request->user_id)->firstOrFail();
        if (AttendanceService::inLockAttendance($request->start_at, $user) || AttendanceService::inLockAttendance($request->end_at, $user)) {
            throw new UnprocessableEntityHttpException('Attendance is locked');
        }

        $request = TimeoffService::requestTimeoffValidation($request);

        DB::beginTransaction();
        try {
            $timeoff = Timeoff::create([
                ...$request->all(),
                'reason' => $request->reason ?? null,
            ]);

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

    public function cancel(Timeoff $timeoff)
    {
        /**
         * if approval_status is approved, reverse timeoff quota from timeoff_quota_histories
         *
         */

        if ($timeoff->approval_status != ApprovalStatus::APPROVED->value && date('Y-m-d', strtotime($timeoff->start_at)) < date('Y-m-d')) {
            return $this->errorResponse(message: 'Cannot cancel past leave', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$timeoff->is_cancelled) {
            DB::beginTransaction();
            try {
                if ($timeoff->approval_status == ApprovalStatus::APPROVED->value) {
                    foreach ($timeoff->timeoff_quota_histories ?? [] as $quota) {
                        $timeoffQuota = TimeoffQuota::select(['id', 'quota', 'used_quota'])->firstWhere('id', $quota['timeoff_quota_id']);
                        if ($timeoffQuota) {
                            $oldBalance = $timeoffQuota->balance;
                            $timeoffQuota->used_quota = max($timeoffQuota->used_quota - $quota['balance'], 0);
                            $timeoffQuota->save();

                            $timeoffQuota->timeoffQuotaHistories()->create([
                                'user_id' => $timeoff->user->id,
                                'is_increment' => true,
                                'old_balance' => $oldBalance,
                                'new_balance' => $timeoffQuota->balance,
                                'description' => "REVERSE QUOTA FROM CANCEL TIMEOFF ($timeoff->id)",
                            ]);
                        }
                    }
                }

                $timeoff->attendances()->delete();

                $timeoff->update([
                    'is_cancelled' => true,
                    'cancelled_by' => auth('sanctum')->id(),
                    'cancelled_at' => now(),
                    'timeoff_quota_histories' => null,
                ]);
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                return $this->errorResponse(message: $e->getMessage());
            }
        }

        return (new TimeoffResource($timeoff))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function approve(Timeoff $timeoff, ApproveRequest $request)
    {
        if ($timeoff->is_cancelled) return $this->errorResponse(message: 'Request is cancelled', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        $requestApproval = $timeoff->approvals()->where('user_id', auth('sanctum')->id())->first();

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
            $timeoff->load(['timeoffPolicy' => fn($q) => $q->select('id', 'type')]);
            if ($timeoff->timeoffPolicy->type->hasQuota()) {
                if ($timeoff->timeoffPolicy->type->is(TimeoffPolicyType::ANNUAL_LEAVE)) {
                    $isQuotaExceeded = TimeoffService::checkTotalBalanceQuotaAL($timeoff->user_id, $timeoff->timeoff_policy_id, $timeoff->total_days, $timeoff->start_at, $timeoff->end_at) === false;
                } else {
                    $remainingBalance = TimeoffService::getTotalBalanceQuota($timeoff->user_id, $timeoff->timeoff_policy_id, $timeoff->start_at, $timeoff->end_at);

                    $isQuotaExceeded = $remainingBalance <= 0 || $remainingBalance < $timeoff->total_days;
                }

                if ($isQuotaExceeded) {
                    return $this->errorResponse(message: 'User Leave balance is not enough', code: Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }

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
            ->whereApprovalStatus($request->filter['approval_status'])
            ->when($request->branch_id, fn($q) => $q->whereBranch($request->branch_id))
            ->when($request->name, fn($q) => $q->whereUserName($request->name))
            ->when($request->created_at, fn($q) => $q->createdAt($request->created_at))
            ->count();

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
                AllowedFilter::exact('cancelled_by'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                'request_type',
                'is_cancelled',
                AllowedFilter::scope('branch_id', 'whereBranch'),
                AllowedFilter::scope('name', 'whereUserName'),
                'created_at',
            ])
            ->allowedIncludes([
                AllowedInclude::callback('timeoffPolicy', fn($query) => $query->selectMinimalist()),
                'cancelledBy'
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'timeoff_policy_id',
                'start_at',
                'end_at',
                'request_type',
                'is_cancelled',
                'cancelled_by',
                'cancelled_at',
                'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }
}
