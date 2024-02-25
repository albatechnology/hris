<?php

namespace App\Observers;

use App\Enums\TimeoffRenewType;
use App\Enums\UserType;
use App\Models\TimeoffRegulation;
use App\Models\User;
use App\Services\TimeoffRegulationService;

class UserObserver
{
    /**
     * Handle the User "saving" event.
     */
    public function saving(User $user): void
    {
        if (!empty($user->branch_id)) {
            $user->company_id = $user->branch?->company_id;
            $user->group_id = $user->branch?->company?->group_id;
        }
    }

    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if (empty($user->type)) {
            $user->type = UserType::USER;
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->detail()->create([
            'join_date' => '2024-01-01'
        ]);
        $user->payrollInfo()->create([]);


        if ($user->type->is(UserType::USER)) {
            $timeoffRegulation = TimeoffRegulation::firstWhere('company_id', $user->company_id);

            if ($timeoffRegulation->renew_type->is(TimeoffRenewType::USER_PERIOD)) {
                // 1. check min_working_month timeoffRegulation
                // 2. check join_date user
                // 3. apabila join_date > min_working_month user tsb berhak mendapatkan jatah cuti

                if (TimeoffRegulationService::isJoinDatePassed($user->detail->join_date, $timeoffRegulation->min_working_month)) {
                    $user->update(['total_timeoff' => $timeoffRegulation->total_day]);
                }
            } elseif ($timeoffRegulation->renew_type->is(TimeoffRenewType::MONTHLY)) {
                // 1. check min_working_month timeoffPeriodRegulations
                // 2. check join_date user
                // 3. urutkan min_working_month dari yang terbesar
                // 4. apabila join_date > min_working_month user tsb berhak mendapatkan jatah cuti

                /** @var TimeoffPeriodRegulation $timeoffPeriodRegulation */
                $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->detail->join_date, $timeoffPeriodRegulation->min_working_month));

                if ($timeoffPeriodRegulation) {
                    // 1. cek join_date user
                    // 2. cek cut off date
                    // 3. user dapet jatah cuti dari awal bulan join_date, tapi perlu di cek dulu cut off date nya. kalo user join nya dibawah cut off date, dia dapet jatah cuti bulan tsb, else gadapet
                    $joinDateMonth = date('m', strtotime($user->detail->join_date));
                    // if()
                    $user->update(['total_timeoff' => $timeoffPeriodRegulation->max_working_month]);
                }
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
