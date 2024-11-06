<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Services\RequestApprovalService;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class RequestApproval extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'requestable_type',
        'requestable_id',
        'user_id',
        'approval_status',
        'approved_at',
        'description',
    ];

    protected $casts = [
        'approval_status' => ApprovalStatus::class
    ];

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            if ($model->isDirty('approval_status')) {
                DB::transaction(function () use ($model) {
                    /** @var RequestedBaseModel $requestable */
                    $requestable = $model->requestable;
                    $requestable->sendApprovedNotification($model->user, $model->approval_status);

                    $user = RequestApprovalService::getUser($requestable);

                    // if approved, kirim notif ke atasan kalo ada
                    if ($model->approval_status->is(ApprovalStatus::APPROVED)) {
                        $supervisor = \App\Classes\SupervisorUtility::build($user, auth()->user())->getSupervisor();

                        if ($supervisor) {
                            $requestable->sendRequestNotification($supervisor->supervisor, $user);
                            // RequestApprovalService::sendNotification($supervisor->supervisor, $user, $requestable);
                        }
                    }

                    // if $requestable->approval_status == approved, update request datanya
                    $requestable->checkAndUpdate($user);
                });
            }
        });

        static::updating(function (self $model) {
            if ($model->isDirty('approval_status')) {
               $model->approved_at = now();
            }
        });
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }
}
