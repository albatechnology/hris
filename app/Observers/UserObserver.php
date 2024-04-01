<?php

namespace App\Observers;

use App\Enums\TimeoffRenewType;
use App\Enums\UserType;
use App\Models\TimeoffPeriodRegulation;
use App\Models\TimeoffRegulation;
use App\Models\User;
use App\Models\UserTimeoffHistory;
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

        if (empty($user->sign_date)) {
            $user->sign_date = '2024-01-26';
            $user->join_date = '2024-01-26';
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->payrollInfo()->create([]);
        $user->detail()->create([]);

        if ($user->type->is(UserType::USER)) {
            $timeoffRegulation = TimeoffRegulation::firstWhere('company_id', $user->company_id);

            if ($timeoffRegulation) {
                $totalTimeoff = 0;

                if ($timeoffRegulation->renew_type->is(TimeoffRenewType::MONTHLY)) {
                    // 1. check min_working_month timeoffPeriodRegulations
                    // 2. check join_date user
                    // 3. urutkan min_working_month dari yang terbesar
                    // 4. apabila join_date > min_working_month user tsb berhak mendapatkan jatah cuti

                    /** @var TimeoffPeriodRegulation $timeoffPeriodRegulation */
                    $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffPeriodRegulation->min_working_month));

                    if ($timeoffPeriodRegulation) {
                        // 1. cek join_date user
                        // 2. cek cut off date
                        // 3. user dapet jatah cuti dari awal bulan join_date, tapi perlu di cek dulu cut off date nya. kalo user join nya dibawah cut off date, dia dapet jatah cuti bulan tsb, else gadapet
                        $startMonth = date('m', strtotime($user->join_date));
                        if ((int)date('d', strtotime($user->join_date)) > (int)$timeoffRegulation->cut_off_date) {
                            $startMonth = date('m', strtotime($user->join_date . '+1 month'));
                        }

                        $endMonth = date('m');
                        if ((int)date('d') < (int)$timeoffRegulation->cut_off_date) {
                            $endMonth = date('m', strtotime('-1 month'));
                        }

                        $totalTimeoff = $timeoffPeriodRegulation->timeoffRegulationMonths()->where('month', '>=', $startMonth)->where('month', '<=', $endMonth)->sum('amount');

                        // $user->update(['total_timeoff' => $totalTimeoff]);
                    }
                } else {
                    // 1. check min_working_month timeoffRegulation
                    // 2. check join_date user
                    // 3. apabila join_date > min_working_month user tsb berhak mendapatkan jatah cuti

                    if (TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffRegulation->min_working_month)) {
                        $totalTimeoff = $timeoffRegulation->total_day;
                        // $user->update(['total_timeoff' => $timeoffRegulation->total_day]);
                    }
                }

                UserTimeoffHistory::create([
                    'user_id' => $user->id,
                    'is_increment' => true,
                    'value' => $totalTimeoff,
                    'properties' => ['user' => $user],
                    'description' => UserTimeoffHistory::DESCRIPTION['USER_CREATED'],
                ]);
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // if ($user->wasChanged('total_timeoff')) {
        // UserTimeoffHistory::create([
        //     'user_id' => $user->id,
        //     'is_increment' => true,
        //     'value' => $user->total_timeoff,
        //     'properties' => ['user' => $user],
        //     'description' => UserTimeoffHistory::DESCRIPTION['USER_CREATED'],
        // ]);
        // }
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
