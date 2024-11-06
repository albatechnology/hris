<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Models\AttendanceDetail;
use App\Models\RequestChangeData;
use App\Models\RequestedBaseModel;
use App\Models\User;
use App\Models\UserSupervisor;
use Illuminate\Support\Facades\DB;

class RequestApprovalService
{
    /**
     * Create approvals for request change data. If setting for request change data
     * approver exists, send notification to approver, else we have to find user
     * supervisor to be approver.
     *
     * @param RequestedBaseModel $requestedbaseModel
     * @return void
     */
    public static function createApprovals(RequestedBaseModel $requestedbaseModel): void
    {
        /** @var User $user this is user requester */
        $user = self::getUser($requestedbaseModel);

        $approvers = self::getApprovers($requestedbaseModel);

        if (count($approvers) <= 0) {
            return;
        }

        /** @var User $approver first supervisor to notify */
        $approver = User::find($approvers[0]['user_id'], ['id', 'name', 'email', 'fcm_token']);

        DB::beginTransaction();
        try {
            $requestedbaseModel->approvals()->createMany($approvers);
            $requestedbaseModel->sendRequestNotification($approver, $user, $requestedbaseModel);
            // self::sendNotification($approver, $user, $requestedbaseModel);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
        }
    }

    public static function getUser(RequestedBaseModel $requestedbaseModel): User
    {
        if ($requestedbaseModel instanceof AttendanceDetail) {
            return $requestedbaseModel->attendance->user;
        }

        /** @var User $user */
        $user = $requestedbaseModel->user;

        return $user;
    }

    /**
     * Get user/requester approvers/supervisors
     * user/requester maybe doenst have approvers/supervisors
     *
     * @param RequestedBaseModel $requestedbaseModel
     * @return array<string, int>|[]
     */
    private static function getApprovers(RequestedBaseModel $requestedbaseModel): array
    {
        /** @var User $user this is user requester */
        $user = self::getUser($requestedbaseModel);
        $approvers = [];

        // check to settings table, if default request approver has been set, use it. else find from user_supervisors
        $defaultApproverId = $user->company->settings()->where('key', SettingKey::REQUEST_APPROVER)->first(['value'])?->value;

        /** @var User $defaultApprover */
        $defaultApprover = User::find($defaultApproverId, ['id']);
        if ($defaultApprover) {
            $approvers[] = [
                'user_id' => $defaultApprover->id,
            ];

            return $approvers;
        }


        // find user supervisors to be approvers
        $user->load(['supervisors' => fn($q) => $q->orderBy('order')]);
        $approvers = $user->supervisors?->map(fn(UserSupervisor $userSupervisor) => [
            'user_id' => $userSupervisor->supervisor_id,
        ])->all();

        return $approvers;
    }

    // /**
    //  * Send notification to user that is set as approver
    //  *
    //  * @param User $receiver
    //  * @param User $requester
    //  * @param RequestedBaseModel $requestedbaseModel
    //  * @return void
    //  */
    // public static function sendNotification(User $receiver, User $requester, RequestedBaseModel $requestedbaseModel): void
    // {
    //     // $notificationType = \App\Enums\NotificationType::REQUEST_CHANGE_DATA;
    //     $notificationType = $requestedbaseModel->getNotificationType();
    //     $receiver?->notify(new ($notificationType->getNotificationClass())($notificationType, $requester, $requestedbaseModel));
    // }
}
