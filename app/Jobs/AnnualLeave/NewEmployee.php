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
        if (config('app.name') == 'LUMORA') {
            $this->lumoraTimeoff();
            return;
        }

        if (config('app.name') == 'SUNSHINE') {
            $this->sunshineTimeoff();
            return;
        }
    }

    private function getQuotaStartDate(Carbon $joinDate): Carbon
    {
        $yearStart = now()->startOfYear();

        // user join sebelum tahun ini → mulai Januari
        if ($joinDate->lt($yearStart)) {
            return $yearStart->copy();
        }

        // user join di tahun ini
        return $joinDate->day > 15
            ? $joinDate->copy()->addMonth()->startOfMonth()
            : $joinDate->copy()->startOfMonth();
    }

    private function getQuotas(Carbon $joinDate)
    {
        $start = $this->getQuotaStartDate($joinDate);
        $end   = now()->endOfYear();

        // safety guard
        if ($start->gt($end)) {
            return collect();
        }

        return collect(CarbonPeriod::create($start, '1 month', $end))
            ->map(function (Carbon $date) {
                return [
                    'quota' => 1,
                    'effective_start_date' => $date->format('Y-m-01'),
                    'effective_end_date'   => $date->copy()->endOfYear()->format('Y-m-d'),
                    'description' => sprintf(
                        'AUTOMATICALLY GENERATED FROM THE SYSTEM (L %s)',
                        $date->format('Y-m')
                    ),
                ];
            });
    }

    private function sunshineTimeoff()
    {
        $threeMonthsAgo = now()->subMonths(3);

        $companies = Company::where('id', 1)->get();

        foreach ($companies as $company) {
            $policyId = $company->timeoffPolicies()
                ->where('type', TimeoffPolicyType::ANNUAL_LEAVE)
                ->value('id');

            $users = User::query()
                ->whereNull('resign_date')
                ->where('company_id', $company->id)
                ->whereDate('join_date', '<=', $threeMonthsAgo)
                // ->get(['id', 'join_date']);
                // ->whereDoesntHave('timeoffQuotas', function ($q) use ($policyId, $yearStart, $yearEnd) {
                //     $q->where('timeoff_policy_id', $policyId)
                //         ->whereBetween('effective_start_date', [$yearStart, $yearEnd]);
                // })
                ->select('id', 'join_date')
                ->where('id', 395)
                ->cursor();

            foreach ($users as $user) {
                $quotas = $this->getQuotas(Carbon::parse($user->join_date));

                DB::transaction(function () use ($user, $quotas, $policyId) {
                    foreach ($quotas as $data) {
                        $quota = $user->timeoffQuotas()->firstOrCreate(
                            [
                                'timeoff_policy_id' => $policyId,
                                'effective_start_date' => $data['effective_start_date'],
                            ],
                            [
                                'effective_end_date' => $data['effective_end_date'],
                                'quota' => $data['quota'],
                            ]
                        );

                        // history hanya kalau quota BARU dibuat
                        if ($quota->wasRecentlyCreated) {
                            $quota->timeoffQuotaHistories()->create([
                                'user_id' => $user->id,
                                'is_increment' => true,
                                'is_automatic' => true,
                                'old_balance' => 0,
                                'new_balance' => $quota->quota,
                                'description' => $data['description'],
                            ]);
                        }
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
