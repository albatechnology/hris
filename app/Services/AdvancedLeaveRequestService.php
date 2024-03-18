<?php

namespace App\Services;

use App\Models\TimeoffRegulation;
use App\Models\User;

class AdvancedLeaveRequestService
{
    public static function getAvailableDays(?User $user = null): int
    {
        if (!$user) {
            /** @var \App\Models\User $user */
            $user = auth('sanctum')->user();
        }

        $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', $user->company_id)->first();

        $availableDays = 0;
        $startMonth = date('m');
        $endMonth = date('m', strtotime('+ ' . $timeoffRegulation->max_advanced_leave_request . 'month'));

        if (
            $timeoffRegulation->min_advanced_leave_working_month &&
            ($user->getTotalWorkingMonth() >= $timeoffRegulation->min_advanced_leave_working_month)
        ) {
            // 1. cek dulu apakah ada timeoff_regulation_months punya user
            // 2. kalo ga ada ambil dari timeoff_regulation_months punya company
            $timeoffRegulationMonths = $user->timeoffRegulationMonths()->where('month', '>', $startMonth)->where('month', '<=', $endMonth)->get(['amount']);
            if ($timeoffRegulationMonths->count() <= 0) {

                /** @var \App\Models\TimeoffPeriodRegulation $timeoffPeriodRegulation */
                $timeoffRegulationMonths = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffPeriodRegulation->min_working_month))?->timeoffRegulationMonths()->where('month', '>', $startMonth)->where('month', '<=', $endMonth)->get(['amount']);
            }

            $availableDays = $timeoffRegulationMonths->sum('amount');
        }

        return $availableDays;
    }
}
