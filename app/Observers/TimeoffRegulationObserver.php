<?php

namespace App\Observers;

use App\Enums\TimeoffRenewType;
use App\Models\TimeoffRegulation;
use App\Models\User;
use App\Services\TimeoffRegulationService;

class TimeoffRegulationObserver
{
    /**
     * Handle the TimeoffRegulation "created" event.
     */
    public function created(TimeoffRegulation $timeoffRegulation): void
    {
    }

    /**
     * Handle the TimeoffRegulation "updated" event.
     */
    // public function updated(TimeoffRegulation $timeoffRegulation): void
    // {
    // dump($timeoffRegulation);
    // $users = User::where('company_id', $timeoffRegulation->company_id)
    //     ->whereHas('detail', fn ($q) => $q->whereNotNull('join_date'))
    //     ->get();

    // dd($users);

    // if ($timeoffRegulation->renew_type->is(TimeoffRenewType::USER_PERIOD)) {
    //     // 1. check min_working_month timeoffRegulation
    //     // 2. check join_date user
    //     // 3. apabila join_date > min_working_month user tsb berhak mendapatkan jatah cuti

    //     // ketika baru created
    //     // 1. update total_timeoff user dengan total_day timeoffRegulation

    //     $users->each(function (User $user) use ($timeoffRegulation) {
    //         if (TimeoffRegulationService::isJoinDatePassed($user->detail->join_date, $timeoffRegulation->min_working_month)) {
    //             $user->update(['total_timeoff' => $timeoffRegulation->total_day]);
    //         }
    //     });
    // } elseif ($timeoffRegulation->renew_type->is(TimeoffRenewType::MONTHLY)) {
    //     // 1. check min_working_month timeoffPeriodRegulations
    //     // 2. check join_date user
    //     // 3. urutkan min_working_month dari yang terbesar
    //     // 4. apabila join_date > min_working_month user tsb berhak mendapatkan jatah cuti

    //     $users->each(function (User $user) use ($timeoffRegulation) {
    //         $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->detail->join_date, $timeoffPeriodRegulation->min_working_month));


    //     });
    // }
    // }

    /**
     * Handle the TimeoffRegulation "deleted" event.
     */
    // public function deleted(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }

    /**
     * Handle the TimeoffRegulation "restored" event.
     */
    // public function restored(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }

    /**
     * Handle the TimeoffRegulation "force deleted" event.
     */
    // public function forceDeleted(TimeoffRegulation $timeoffRegulation): void
    // {
    //     //
    // }
}
