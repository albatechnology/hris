<?php

namespace App\Jobs\AnnualLeave;

use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NewEmployeeCharyuning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // if (config('app.name') == 'LUMORA') {
        //     $this->lumoraTimeoff();
        //     return;
        // }

        // if (config('app.name') == 'SUNSHINE') {
        //     $this->sunshineTimeoff();
        //     return;
        // }
    }

    private function getQuotas(Carbon $joinDate)
    {
        if ($joinDate->format('d') > 15) {
            $joinDate = $joinDate->copy()->addMonth();
        }

        return collect(CarbonPeriod::create($joinDate, '1 month', date('Y-12-31')))
            ->filter(function ($joinDate) {
                return $joinDate->format('Y') === date('Y');
            })
            ->map(function ($joinDate) {
                return [
                    'quota' => 1,
                    'effective_start_date' => $joinDate->format('Y-m-01'),
                    'effective_end_date' => $joinDate->format('Y') . '-12-31',
                    'description' => sprintf('AUTOMATICALLY GENERATED FROM THE SYSTEM (L %s)', $joinDate->format('Y-m-01'))
                ];
            })->values();
    }

    private function sunshineTimeoff()
    {
        /**
         * 1. get users yang masa kerjanya < 1 tahun
         * 2. where belum dapet jatah cuti di tahun ini
         * 3. where masa kerjanya >= 3 bulan
         * 4. user eligible to get the quota of the month user join, if join_date <= 15
         */

        // date to compare that the user should be working at least 3 months since join_date
        $joinDate = Carbon::now()->subMonths(4);

        $dataQuotas = $this->getQuotas($joinDate);
        // dump($joinDate);
        // dump($dataQuotas);
        $companies = Company::select('id')->where('id', 1)->get();
        foreach ($companies as $company) {
            $timeoffPolicyId = $company->timeoffPolicies()->where('type', TimeoffPolicyType::ANNUAL_LEAVE)->first(['id'])->id;
            $users = User::query()
                ->where('id', 305)
                // ->where('company_id', $company->id)
                // ->whereDate('join_date', '>', now()->subYear())
                // ->whereDate('join_date', '<=', $joinDate)
                // ->whereYear('join_date', $joinDate->format('Y'))
                // ->whereMonth('join_date', '<=', $joinDate->format('m'))
                // ->whereDay('join_date', '<=', 15)
                // ->whereDoesntHave(
                //     'timeoffQuotas',
                //     fn($q) => $q->whereHas('timeoffPolicy', fn($q) => $q->where('type', TimeoffPolicyType::ANNUAL_LEAVE))
                //         ->whereHas(
                //             'timeoffQuotaHistories',
                //             fn($q) => $q->where('is_automatic', true)->whereYear('created_at', $joinDate->format('Y'))
                //         )
                // )
                ->get(['id']);
            // dd($users->select('id', 'name', 'join_date')->toArray());

            foreach ($users as $user) {
                DB::transaction(function () use ($user, $dataQuotas, $timeoffPolicyId) {
                    foreach ($dataQuotas as $dataQuota) {
                        $timeoffQuota = $user->timeoffQuotas()->create([
                            'timeoff_policy_id' => $timeoffPolicyId,
                            'effective_start_date' => $dataQuota['effective_start_date'],
                            'effective_end_date' => $dataQuota['effective_end_date'],
                            'quota' => $dataQuota['quota'],
                        ]);

                        $timeoffQuota->timeoffQuotaHistories()->create([
                            'user_id' => $timeoffQuota->user_id,
                            'is_increment' => true,
                            'is_automatic' => true,
                            'new_balance' => $timeoffQuota->quota,
                            'description' => $dataQuota['description'],
                        ]);
                    }
                });
            }
        }
    }
    private function lumoraTimeoff()
    {
        $today = date('Y-m-d');
        $todayNextYear = date('Y-m-d', strtotime('+1 year'));
        $year = date('Y');

        $companies = Company::select('id')->get();
        foreach ($companies as $company) {
            $timeoffPolicyId = $company->timeoffPolicies()->where('type', TimeoffPolicyType::ANNUAL_LEAVE)->first(['id'])?->id;

            if (!$timeoffPolicyId) continue;

            $users = User::where('company_id', $company->id)
                ->whereDate('join_date', $today)->whereYear('join_date', '<', $year)
                ->whereDoesntHave(
                    'timeoffQuotas',
                    fn($q) => $q->whereHas('timeoffPolicy', fn($q) => $q->where('type', TimeoffPolicyType::ANNUAL_LEAVE))
                        ->whereHas(
                            'timeoffQuotaHistories',
                            fn($q) => $q->where('is_automatic', true)->whereYear('created_at', $year)
                        )
                )
                ->orderBy('join_date')
                ->get(['id', 'join_date', 'name']);

            foreach ($users as $user) {
                DB::transaction(function () use ($user, $timeoffPolicyId, $today, $todayNextYear) {
                    $timeoffQuota = $user->timeoffQuotas()->create([
                        'timeoff_policy_id' => $timeoffPolicyId,
                        'effective_start_date' => $today,
                        'effective_end_date' => $todayNextYear,
                        'quota' => 12,
                    ]);

                    $timeoffQuota->timeoffQuotaHistories()->create([
                        'user_id' => $timeoffQuota->user_id,
                        'is_increment' => true,
                        'is_automatic' => true,
                        'new_balance' => $timeoffQuota->quota,
                        'description' => "AUTOMATICALLY GENERATED FROM THE SYSTEM AT " . $today,
                    ]);
                });
            }
        }
    }
}
