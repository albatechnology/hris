<?php

namespace App\Http\Services\UserTransfer;

use App\Models\UserTransfer;
use Illuminate\Support\Facades\DB;

class UserTransferService
{
    public function execute(UserTransfer $userTransfer): UserTransfer
    {
        DB::beginTransaction();
        try {
            /** @var \App\Models\User */
            $user = $userTransfer->user;
            $userHasUpdated = false;
            if ($userTransfer->company_id) {
                $user->companies()->delete();
                $user->companies()->create(['company_id' => $userTransfer->company_id]);
                $user->company_id = $userTransfer->company_id;
                $userHasUpdated = true;
            }
            if ($userTransfer->branch_id) {
                $user->branches()->delete();
                $user->branches()->create(['branch_id' => $userTransfer->branch_id]);
                $user->branch_id = $userTransfer->branch_id;
                $userHasUpdated = true;
            }
            if ($userTransfer->supervisor_id) {
                $user->supervisors()->delete();
                $user->supervisors()->create(['supervisor_id' => $userTransfer->supervisor_id]);
            }
            if ($userTransfer->department_id && $userTransfer->position_id) {
                $user->positions()->delete();
                $user->positions()->create(['position_id' => $userTransfer->position_id, 'department_id' => $userTransfer->department_id]);
            }
            if ($userTransfer->employment_status) {
                $user->detail()->update([
                    'employment_status' => $userTransfer->employment_status
                ]);
            }
            if ($userHasUpdated) {
                $user->save();
            }

            $userTransfer->update([
                'executed_at' => now()
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $userTransfer;
    }
}
