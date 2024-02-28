<?php

namespace App\Jobs\Timeoff;

use App\Enums\TimeoffRenewType;
use App\Enums\UserType;
use App\Jobs\Timeoff\CleanRemainingTimeoff;
use App\Models\Company;
use App\Models\User;
use App\Models\UserTimeoffHistory;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReevaluateTimeoffRegulationPeriod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $companies = Company::whereHas('timeoffRegulation', fn ($q) => $q->where('renew_type', TimeoffRenewType::PERIOD)->whereMonth('end_period', date('m'))->whereDay('end_period', date('d')))
            ->with('timeoffRegulation', fn ($q) => $q->select('id', 'company_id', 'total_day', 'is_expired_in_end_period', 'expired_max_month'))
            ->get();

        $companies->each(function (Company $company) {
            /** @var \App\Models\TimeoffRegulation $timeoffRegulation */
            $timeoffRegulation = $company->timeoffRegulation;

            User::where('company_id', $company->id)
                ->where('type', UserType::USER)
                ->get()->each(function (User $user) use ($timeoffRegulation) {
                    if (
                        !$timeoffRegulation->is_expired_in_end_period &&
                        !is_null($timeoffRegulation->expired_max_month) &&
                        $timeoffRegulation->expired_max_month > 0
                    ) {
                        UserTimeoffHistory::create([
                            'is_for_total_timeoff' => false,
                            'user_id' => $user->id,
                            'is_increment' => true,
                            'value' => $user->total_timeoff,
                            'properties' => ['user' => $user],
                            'description' => UserTimeoffHistory::DESCRIPTION['ADD_REMAINING_TIMEOFF'],
                        ]);

                        // create cron for clean user remaining_timeoff in expired_max_month
                        CleanRemainingTimeoff::dispatch($user)->delay(now()->addMonths($timeoffRegulation->expired_max_month));
                    }

                    UserTimeoffHistory::create([
                        'user_id' => $user->id,
                        'is_increment' => false,
                        'value' => $user->total_timeoff,
                        'properties' => ['user' => $user],
                        'description' => UserTimeoffHistory::DESCRIPTION['PERIOD_EXPIRED'],
                    ]);
                });
        });
    }
}
