<?php

namespace App\Jobs\Timeoff;

use App\Enums\TimeoffRenewType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use App\Models\UserTimeoffHistory;
use App\Services\TimeoffRegulationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshTimeoffRegulationMonthly implements ShouldQueue
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
        $companies = Company::whereHas('timeoffRegulation', fn ($q) => $q->where('renew_type', TimeoffRenewType::MONTHLY)->where('cut_off_date', date('d')))
            ->with('timeoffRegulation', fn ($q) => $q->select('id', 'company_id', 'total_day', 'is_expired_in_end_period', 'expired_max_month', 'start_period_date', 'end_period_date', 'start_period_month', 'end_period_month', 'cut_off_date', 'min_working_month'))
            ->get(['id']);

        $companies->each(function (Company $company) {
            /** @var \App\Models\TimeoffRegulation $timeoffRegulation */
            $timeoffRegulation = $company->timeoffRegulation;

            User::where('company_id', $company->id)
                ->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])
                ->get(['id', 'company_id', 'join_date', 'total_timeoff'])
                ->each(function (User $user) use ($timeoffRegulation) {

                    // 1. kalo min_working_month user == min_working_month company, maka dia baru pertama kali dapet jatah cuti
                    // 2. else(no 1) berarti kasih jatah cuti bulan ini aja

                    $userWorkingMonth = $user->getTotalWorkingMonth($timeoffRegulation->cut_off_date, true);

                    $totalTimeoff = 0;
                    if ($userWorkingMonth['total_month'] >= $timeoffRegulation->min_working_month) {
                        if ($userWorkingMonth['total_month'] == $timeoffRegulation->min_working_month) {

                            $startMonth = date('m', strtotime($user->join_date));
                            if ((int)date('d', strtotime($user->join_date)) > (int)$timeoffRegulation->cut_off_date) {
                                $startMonth = date('m', strtotime($user->join_date . '+1 month'));
                            }

                            $endMonth = date('m');
                            if ((int)date('d') < (int)$timeoffRegulation->cut_off_date) {
                                $endMonth = date('m', strtotime('-1 month'));
                            }

                            if ($userWorkingMonth['join_date']->format('Y') < $userWorkingMonth['cut_off_date']->format('Y')) {
                                $intervalYear = $userWorkingMonth['cut_off_date']->format('Y') - $userWorkingMonth['join_date']->format('Y');
                                $totalTimeoff += ($intervalYear * 12) - $startMonth;
                                $startMonth = "01";
                            }

                            $totalTimeoff = 0;
                            if ($user->timeoffRegulationMonths?->count() > 0) {
                                $totalTimeoff += $user->timeoffRegulationMonths()->where('month', '>=', $startMonth)->where('month', '<=', $endMonth)->sum('amount');
                            } else {
                                $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffPeriodRegulation->min_working_month));

                                if ($timeoffPeriodRegulation) {
                                    $totalTimeoff += $timeoffPeriodRegulation->timeoffRegulationMonths()->where('month', '>=', $startMonth)->where('month', '<=', $endMonth)->sum('amount');
                                }
                            }
                        } else {
                            if ($user->timeoffRegulationMonths?->count() > 0) {
                                $totalTimeoff = $user->timeoffRegulationMonths()->where('month', date('m'))->first(['amount'])?->amount ?? 0;
                            } else {
                                $timeoffPeriodRegulation = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffPeriodRegulation->min_working_month));

                                if ($timeoffPeriodRegulation) {
                                    $totalTimeoff = $timeoffPeriodRegulation->timeoffRegulationMonths()->where('month', date('m'))->first(['amount'])?->amount ?? 0;
                                }
                            }
                        }
                    }

                    UserTimeoffHistory::create([
                        'user_id' => $user->id,
                        'is_increment' => true,
                        'value' => $totalTimeoff,
                        'properties' => ['user' => $user],
                        'description' => UserTimeoffHistory::DESCRIPTION['PERIOD_RENEWED'],
                    ]);
                });
        });
    }
}
