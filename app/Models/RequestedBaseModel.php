<?php

namespace App\Models;

use App\Classes\SupervisorUtility;
use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use App\Enums\MediaCollection;
use App\Enums\NotificationType;
use App\Enums\RequestChangeDataType;
use App\Interfaces\Requested;
use App\Services\RequestApprovalService;
use App\Services\TimeoffService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

abstract class RequestedBaseModel extends BaseModel implements Requested
{
    protected $appends = [
        'approval_status'
    ];

    protected static function booted(): void
    {
        static::created(function (self $model) {
            if (!$model instanceof AttendanceDetail || ($model instanceof AttendanceDetail && $model->type->is(AttendanceType::MANUAL))) {
                RequestApprovalService::createApprovals($model);
            }
        });
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(RequestApproval::class, 'requestable');
    }

    public function scopeWhereBranch(Builder $q, int $value)
    {
        $q->whereHas('user', fn($q) => $q->where('branch_id', $value));
    }
    public function scopeWhereUserName(Builder $q, string $value)
    {
        $q->whereHas('user', fn($q) => $q->whereLike('name', $value));
    }

    public function scopeApproved(Builder $query)
    {
        $query->has('approvals')
            ->whereDoesntHave('approvals', fn($q) => $q->whereIn('approval_status', [ApprovalStatus::PENDING, ApprovalStatus::REJECTED, ApprovalStatus::ON_PROGRESS]));
    }

    public function scopeWhereApprovalStatus(Builder $query, string|ApprovalStatus $status = ApprovalStatus::PENDING->value): Builder
    {
        if ($status instanceof ApprovalStatus) $status = $status->value;

        if ($status == ApprovalStatus::PENDING->value) {
            return $query
                ->whereHas('approvals', fn($q) => $q->where('approval_status', ApprovalStatus::PENDING))
                ->whereDoesntHave('approvals', fn($q) => $q->whereIn('approval_status', [ApprovalStatus::REJECTED, ApprovalStatus::APPROVED]));
        } elseif ($status == ApprovalStatus::ON_PROGRESS->value) {
            return $query->whereHas('approvals', fn($q) => $q->whereIn('approval_status', [ApprovalStatus::PENDING, ApprovalStatus::APPROVED]))
                ->whereDoesntHave('approvals', fn($q) => $q->where('approval_status', ApprovalStatus::REJECTED));
        } elseif ($status == ApprovalStatus::APPROVED->value) {
            return $query->whereDoesntHave('approvals', fn($q) => $q->whereIn('approval_status', [ApprovalStatus::PENDING, ApprovalStatus::REJECTED]));
        } elseif ($status == ApprovalStatus::REJECTED->value) {
            return $query
                ->whereHas('approvals', fn($q) => $q->where('approval_status', ApprovalStatus::REJECTED))
                ->whereDoesntHave('approvals', fn($q) => $q->whereIn('approval_status', [ApprovalStatus::PENDING, ApprovalStatus::APPROVED]));
        }

        // on_progress
        return $query;
        // return $query->whereHas('approvals', fn($q) => $q->where('approval_status', ApprovalStatus::PENDING))
        //     ->whereHas('approvals', fn($q) => $q->whereIn('approval_status', [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED]));
    }

    public function scopeMyApprovals(Builder $query): void
    {
        $query->whereHas('approvals', fn($q) => $q->where('user_id', auth('sanctum')->id()));
    }

    /**
     * Send notification to user that is set as approver
     *
     * @param User $receiver
     * @param User $requester
     * @param self $model
     * @return void
     */
    public function sendRequestNotification(User $receiver, User $requester): void
    {
        $notificationType = $this->getNotificationType();
        $receiver?->notify(new ($notificationType->getNotificationClass())($notificationType, $requester, $this));
    }

    public function sendApprovedNotification(User $sender, ApprovalStatus $approvalStatus): void
    {
        $notificationType = $this->getNotificationType(true);
        $user = RequestApprovalService::getUser($this);

        $user->notify(new ($notificationType->getNotificationClass())($notificationType, $sender, $approvalStatus, $this));
    }

    public function getNotificationType(bool $isApproved = false): NotificationType
    {
        if ($this instanceof RequestChangeData) {
            if ($isApproved) return NotificationType::REQUEST_CHANGE_DATA_APPROVED;
            return NotificationType::REQUEST_CHANGE_DATA;
        } elseif ($this instanceof AttendanceDetail) {
            if ($isApproved) return NotificationType::ATTENDANCE_APPROVED;
            return NotificationType::REQUEST_ATTENDANCE;
        } elseif ($this instanceof RequestSchedule) {
            if ($isApproved) return NotificationType::REQUEST_SCHEDULE_APPROVED;
            return NotificationType::REQUEST_SCHEDULE;
        } elseif ($this instanceof OvertimeRequest) {
            if ($isApproved) return NotificationType::OVERTIME_APPROVED;
            return NotificationType::REQUEST_OVERTIME;
        } elseif ($this instanceof Timeoff) {
            if ($isApproved) return NotificationType::TIMEOFF_APPROVED;
            return NotificationType::REQUEST_TIMEOFF;
        } elseif ($this instanceof AdvancedLeaveRequest) {
            if ($isApproved) return NotificationType::ADVANCED_LEAVE_APPROVED;
            return NotificationType::REQUEST_ADVANCED_LEAVE;
        } elseif ($this instanceof TaskRequest) {
            if ($isApproved) return NotificationType::TASK_APPROVED;
            return NotificationType::REQUEST_TASK;
        } elseif ($this instanceof RequestShift) {
            if ($isApproved) return NotificationType::REQUEST_SHIFT_APPROVED;
            return NotificationType::REQUEST_SHIFT;
        } elseif ($this instanceof Reimbursement) {
            if ($isApproved) return NotificationType::REQUEST_REIMBURSEMENT_APPROVED;
            return NotificationType::REQUEST_REIMBURSEMENT;
        }
    }

    public function getApprovalStatusAttribute(): string
    {
        if ($this->approvals->every(fn($approval) => $approval->approval_status == ApprovalStatus::PENDING)) {
            return ApprovalStatus::PENDING->value;
        }
        if ($this->approvals->every(fn($approval) => $approval->approval_status == ApprovalStatus::APPROVED)) {
            return ApprovalStatus::APPROVED->value;
        }
        if ($this->approvals->contains(fn($approval) => $approval->approval_status == ApprovalStatus::REJECTED)) {
            return ApprovalStatus::REJECTED->value;
        }

        return ApprovalStatus::ON_PROGRESS->value;
    }

    public function isDescendantApproved(?User $user = null): bool
    {
        return true;

        // need to create setting for direct approver
        if (!$user) $user = auth()->user();
        $descendant = SupervisorUtility::build(RequestApprovalService::getUser($this), $user)->getSupervisor(false);
        if (!$descendant) return true;

        $approved = $this->approvals->where('user_id', $descendant->supervisor->id)->where('approval_status', ApprovalStatus::APPROVED)->first();

        return !!$approved;
    }

    public function checkAndUpdate(?User $user = null): void
    {
        if (!$user) {
            $user = RequestApprovalService::getUser($this);
        }

        if ($this->approval_status == ApprovalStatus::APPROVED->value) {
            if ($this instanceof RequestChangeData) {
                $this->details->each(function (RequestChangeDataDetail $detail) use ($user) {
                    if ($detail->type->is(RequestChangeDataType::PHOTO_PROFILE)) {
                        $detail->getFirstMedia(MediaCollection::REQUEST_CHANGE_DATA->value)->copy($user, MediaCollection::USER->value);
                    } else {
                        RequestChangeDataType::updateData($detail->type, $user->id, $detail->value);
                    }
                });
            } elseif ($this instanceof RequestSchedule) {
                $schedule = Schedule::where([
                    ['company_id', '=', $this->company_id],
                    ['type', '=', $this->type->value],
                    // ['name, '=',> $this->name],
                    ['effective_date', '=', $this->effective_date],
                    ['is_overide_national_holiday', '=', $this->is_overide_national_holiday],
                    ['is_overide_company_holiday', '=', $this->is_overide_company_holiday],
                    ['is_include_late_in', '=', $this->is_include_late_in],
                    ['is_include_early_out', '=', $this->is_include_early_out],
                    ['is_flexible', '=', $this->is_flexible],
                    ['is_generate_timeoff', '=', $this->is_generate_timeoff],
                ])->first();

                if (!$schedule) {
                    $schedule = Schedule::create([
                        'company_id' => $this->company_id,
                        'type' => $this->type,
                        'name' => $this->name,
                        'effective_date' => $this->effective_date,
                        'is_overide_national_holiday' => $this->is_overide_national_holiday,
                        'is_overide_company_holiday' => $this->is_overide_company_holiday,
                        'is_include_late_in' => $this->is_include_late_in,
                        'is_include_early_out' => $this->is_include_early_out,
                        'is_flexible' => $this->is_flexible,
                        'is_generate_timeoff' => $this->is_generate_timeoff,
                    ]);

                    $order = 1;
                    foreach ($this->requestScheduleShifts->sortByDesc('order') ?? [] as $shift) {
                        $schedule->shifts()->attach($shift['shift_id'], ['order' => $order++]);
                    }
                }

                $schedule->users()->syncWithoutDetaching([$this->user_id]);
            } elseif ($this instanceof Timeoff) {
                TimeoffService::approved($this);
            } elseif ($this instanceof RequestShift) {
                $this->requestApproved();
            }
        }
    }
}
