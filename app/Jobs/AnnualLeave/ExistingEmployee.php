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

/**
 * this job must be running at the first day of every year
 */
class ExistingEmployee implements ShouldQueue
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
        // prepare year at first for start_effective_date & end_effective_date rather than generate every call getQuotas() function
        $year = date('Y');

        // date for calculate how long the user has been working
        $today = Carbon::now()->startOfYear();

        $companies = Company::select('id')->where('id', 1)->get();
        foreach ($companies as $company) {
            $timeoffPolicyId = $company->timeoffPolicies()->where('type', TimeoffPolicyType::ANNUAL_LEAVE)->first(['id'])->id;
            $users = User::query()
                ->where('company_id', $company->id)
                ->whereDate('join_date', '<=', now()->subYear())
                ->get(['id', 'join_date']);

            foreach ($users as $user) {
                $dataQuotas = $this->getQuotas($user->join_date, $today, $year);

                DB::transaction(function () use ($user, $dataQuotas, $timeoffPolicyId) {
                    foreach ($dataQuotas as $dataQuota) {
                        $description = sprintf('AUTOMATICALLY GENERATED FROM THE SYSTEM (L %s)', $dataQuota['effective_start_date']);

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
                            'description' => $description,
                        ]);
                    }
                });
            }
        }
    }

    public function getQuotas(string $joinDate, Carbon $compareDate, string $year): array
    {
        $effectiveEndDate = $year . '-12-31';
        $data = [
            // [
            //     'quota' => 1,
            //     'effective_start_date' => $year . '-01-01',
            //     'effective_end_date' => $effectiveEndDate,
            // ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-02-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-03-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-04-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-05-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-06-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-07-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-08-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-09-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-10-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-11-01',
                'effective_end_date' => $effectiveEndDate,
            ],
            [
                'quota' => 1,
                'effective_start_date' => $year . '-12-01',
                'effective_end_date' => $effectiveEndDate,
            ],
        ];

        $joinDate = Carbon::parse($joinDate);
        $diffInYears = $joinDate->diffInYears($compareDate);
        if ($diffInYears > 3 && $diffInYears <= 5) {
            $data = [
                // [
                //     'quota' => 2,
                //     'effective_start_date' => $year . '-01-01',
                //     'effective_end_date' => $effectiveEndDate,
                // ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-02-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-03-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-04-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-05-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-06-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 2,
                    'effective_start_date' => $year . '-07-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-08-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-09-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-10-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-11-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-12-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
            ];
        } elseif ($diffInYears > 5) {
            $data = [
                // [
                //     'quota' => 2,
                //     'effective_start_date' => $year . '-01-01',
                //     'effective_end_date' => $effectiveEndDate,
                // ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-02-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-03-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 2,
                    'effective_start_date' => $year . '-04-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-05-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-06-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 2,
                    'effective_start_date' => $year . '-07-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-08-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-09-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 2,
                    'effective_start_date' => $year . '-10-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-11-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
                [
                    'quota' => 1,
                    'effective_start_date' => $year . '-12-01',
                    'effective_end_date' => $effectiveEndDate,
                ],
            ];
        }

        return $data;
    }
}
