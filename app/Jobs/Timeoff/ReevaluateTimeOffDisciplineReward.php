<?php

namespace App\Jobs\Timeoff;

use App\Enums\UserType;
use App\Jobs\Timeoff\CleanRemainingTimeoff;
use App\Models\Company;
use App\Models\User;
use App\Models\UserTimeoffHistory;
use App\Services\TimeoffRegulationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReevaluateTimeOffDisciplineReward implements ShouldQueue
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
        $companies = Company::get(['id']);

        $companies->each(function (Company $company) {
            User::where('company_id', $company->id)
                ->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])
                ->whereDoesntHave('timeoffHistories', function($q){
                  $q->where('description', UserTimeoffHistory::DESCRIPTION['DISCIPLINE_REWARD']);
                  $q->whereDate('created_at', Carbon::today()->subMonths(4));
                })
                ->whereDoesntHave('attendances.attendanceDetails', function($q){
                  $q->where('description', UserTimeoffHistory::DESCRIPTION['DISCIPLINE_REWARD']);
                  $q->whereDate('created_at', Carbon::today()->subMonths(4));
                })
                ->get(['id', 'join_date', 'total_timeoff'])
                ->each(function (User $user) {

                    // blm slsai

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
                            'description' => UserTimeoffHistory::DESCRIPTION['DISCIPLINE_REWARD'],
                        ]);

                        // create cron for clean user total_remaining_timeoff in expired_max_month
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

            TimeoffRegulationService::updateEndPeriod($timeoffRegulation);
        });
    }
}
