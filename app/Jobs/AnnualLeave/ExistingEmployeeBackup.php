<?php

namespace App\Jobs\AnnualLeave;

use App\Enums\TimeoffPolicyType;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExistingEmployeeBackup implements ShouldQueue
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
        $today = Carbon::now()->startOfMonth();
        $date = Carbon::now()->startOfMonth()->subMonths(3);
        $startYearDate = date('Y-01-01');
        $endYearDate = date('Y-12-31');
        $description = sprintf('AUTOMATICALLY GENERATED FROM THE SYSTEM (L %s)', $date->format('F Y'));

        $companies = Company::select('id')->get();
        foreach ($companies as $company) {
            $timeoffPolicy = $company->timeoffPolicies()->where('type', TimeoffPolicyType::TIME_OFF)->first(['id']);
            $users = User::query()
                ->where('company_id', $company->id)
                ->whereMonth('join_date', '<', $date->format('Y-m-d'))
                ->get(['id', 'join_date']);

            foreach ($users as $user) {
                $totalQuota = $this->getQuota($user->join_date, $today);

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

    public function getQuota($joinDate, Carbon $compareDate): int
    {
        $joinDate = Carbon::parse($joinDate);
        $year = $joinDate->diffInYears($compareDate);
        $totalQuota = 1;
        if ($year > 3 && $year <= 5) {
            $totalQuota = match ($compareDate->format('m')) {
                '01',
                '07' => 2,
                default => 1,
            };
        } elseif ($year > 5) {
            $totalQuota = match ($compareDate->format('m')) {
                '01',
                '04',
                '07',
                '10' => 2,
                default => 1,
            };
        }

        return $totalQuota;
    }
}
