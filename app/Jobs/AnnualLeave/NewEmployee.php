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

class NewEmployee implements ShouldQueue
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
        /**
         * 1. get users yang masa kerjanya < 1 tahun
         * 2. where belum dapet jatah cuti di tahun ini
         * 3. where masa kerjanya >= 3 bulan
         * 4. user eligible to get the quota of the month user join, if join_date <= 15
         */

        // date to compare that the user should be working at least 3 months since join_date
        $joinDate = Carbon::now()->setDay(15)->subMonths(3);

        $dataQuotas = $this->getQuotas($joinDate);
        dd($dataQuotas);

        $companies = Company::select('id')->where('id', 1)->get();
        foreach ($companies as $company) {
            $timeoffPolicyId = $company->timeoffPolicies()->where('type', TimeoffPolicyType::ANNUAL_LEAVE)->first(['id'])->id;
            $users = User::query()
                ->where('company_id', $company->id)
                ->whereDate('join_date', '>', now()->subYear())
                ->whereDate('join_date', '<=', $joinDate)
                // ->whereYear('join_date', $joinDate->format('Y'))
                // ->whereMonth('join_date', '<=', $joinDate->format('m'))
                // ->whereDay('join_date', '<=', 15)
                ->whereDoesntHave(
                    'timeoffQuotas',
                    fn($q) => $q->whereHas('timeoffPolicy', fn($q) => $q->where('type', TimeoffPolicyType::ANNUAL_LEAVE))
                        ->whereHas(
                            'timeoffQuotaHistories',
                            fn($q) => $q->where('is_automatic', true)->whereYear('created_at', $joinDate->format('Y'))
                        )
                )
                ->orderBy('join_date')
                ->limit(2)
                ->get(['id', 'join_date']);

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

    private function getQuotas(Carbon $joinDate)
    {
        return collect(CarbonPeriod::create($joinDate, '1 month', date('Y-12-31')))
            // ->filter(fn($joinDate) => $joinDate->format('Y') === date('Y'))
            ->filter(function ($joinDate) {
                return $joinDate->format('Y') === date('Y') && $joinDate->format('m') !== '01';
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
}
