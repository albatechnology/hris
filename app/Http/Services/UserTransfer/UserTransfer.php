<?php

namespace App\Http\Services\UserTransfer;

use App\Models\UserTransfer;

class UserTransferService
{
    public function execute(UserTransfer $userTransfer): UserTransfer
    {
        // 'position_id',
        // 'department_id',
        // 'employment_status',

        /** @var \App\Models\User */
        $user = $userTransfer->user;
        $userHasUpdated = false;
        if ($userTransfer->company_id) {
            $user->companies()->delete();
            $user->companies()->attach($userTransfer->company_id);
            $user->company_id = $userTransfer->company_id;
            $userHasUpdated = true;
        }
        if ($userTransfer->branch_id) {
            $user->branches()->delete();
            $user->branches()->attach($userTransfer->branch_id);
            $user->branch_id = $userTransfer->branch_id;
            $userHasUpdated = true;
        }
        if ($userTransfer->supervisor_id) {
            $user->supervisors()->delete();
            $user->supervisors()->attach($userTransfer->supervisor_id);
            $userHasUpdated = true;
        }
        if ($userTransfer->department_id && $userTransfer->position_id) {
            $user->positions()->delete();
            $user->positions()->createMany(['position_id' => $userTransfer->position_id, 'department_id' => $userTransfer->department_id]);
            $userHasUpdated = true;
        }
        if ($userTransfer->supervisor_id) {
            $user->supervisor_id = $userTransfer->supervisor_id;
            $userHasUpdated = true;
        }
        if ($userHasUpdated) {
            $user->save();
        }

        return $userTransfer;
    }
}
