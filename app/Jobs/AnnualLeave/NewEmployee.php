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
        $today = Carbon::now()->setDay(15);
        $date = Carbon::now()->startOfMonth()->subMonths(3);
        $startYearDate = date('Y-01-01');
        $endYearDate = date('Y-12-31');

        $dataQuota = collect(CarbonPeriod::create($date, '1 month', $today))
            ->map(fn($date) => $date->format('F-Y'))
            ->filter(fn($month) => str_contains($month, $today->format('Y')));
        $totalQuota = $dataQuota->count();
        $description = sprintf('AUTOMATICALLY GENERATED FROM THE SYSTEM (L %s)', $dataQuota->implode(', '));

        $companies = Company::select('id')->get();
        foreach ($companies as $company) {
            $timeoffPolicy = $company->timeoffPolicies()->where('type', TimeoffPolicyType::TIME_OFF)->first(['id']);
            $users = User::query()
                ->where('company_id', $company->id)
                ->whereYear('join_date', $date->format('Y'))
                ->whereMonth('join_date', $date->format('m'))
                ->whereDay('join_date', '<=', 15)
                ->get(['id', 'join_date']);

            foreach ($users as $user) {
                DB::transaction(function () use ($user, $totalQuota, $timeoffPolicy, $startYearDate, $endYearDate, $description) {
                    $timeoffQuota = $user->timeoffQuotas()->create([
                        'timeoff_policy_id' => $timeoffPolicy->id,
                        'effective_start_date' => $startYearDate,
                        'effective_end_date' => $endYearDate,
                        'quota' => $totalQuota,
                    ]);

                    $timeoffQuota->timeoffQuotaHistories()->create([
                        'user_id' => $timeoffQuota->user_id,
                        'is_increment' => true,
                        'new_balance' => $timeoffQuota->quota,
                        'description' => $description,
                    ]);
                });
            }
        }
    }
}
