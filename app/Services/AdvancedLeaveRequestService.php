<?php

namespace App\Services;

use App\Models\AdvancedLeaveRequest;
use App\Models\TimeoffRegulation;
use App\Models\User;

class AdvancedLeaveRequestService
{
    public static function updateMonths(AdvancedLeaveRequest $advancedLeaveRequest, ?User $user = null): void
    {
        if (!$user) {
            /** @var User $user */
            $user = $advancedLeaveRequest->user;
        }

        $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', $user->company_id)->first();

        $totalDayAdvanceLeaveRequest = $advancedLeaveRequest->amount;
        $startMonth = date('m');
        $endMonth = date('m', strtotime('+ ' . $timeoffRegulation->max_advanced_leave_request . 'month'));

        if ($user->timeoffRegulationMonths->count() > 0) {
            $timeoffRegulationMonths = $user->timeoffRegulationMonths;
            $oldTimeoffRegulationMonths = $timeoffRegulationMonths;
            foreach ($timeoffRegulationMonths as $timeoffRegulationMonth) {
                if (
                    $timeoffRegulationMonth->month > $startMonth &&
                    $timeoffRegulationMonth->month <= $endMonth &&
                    $totalDayAdvanceLeaveRequest > 0
                ) {
                    $amount = max($timeoffRegulationMonth->amount - $totalDayAdvanceLeaveRequest, 0);
                    $totalDayAdvanceLeaveRequest -= $timeoffRegulationMonth->amount;
                    $timeoffRegulationMonth->amount = $amount;
                } elseif ($timeoffRegulationMonth->month < $startMonth) {
                    $timeoffRegulationMonth->amount = 0;
                }
                // handle total amount untuk bulan $startMonth
                // elseif($timeoffRegulationMonth->month == $startMonth) {
                // }
            }

            $user->timeoffRegulationMonths()->delete();
        } else {
            $timeoffRegulationMonths = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffPeriodRegulation->min_working_month))?->timeoffRegulationMonths;

            $oldTimeoffRegulationMonths = $timeoffRegulationMonths;
            foreach ($timeoffRegulationMonths as $timeoffRegulationMonth) {
                if (
                    $timeoffRegulationMonth->month > $startMonth &&
                    $timeoffRegulationMonth->month <= $endMonth &&
                    $totalDayAdvanceLeaveRequest > 0
                ) {
                    $amount = max($timeoffRegulationMonth->amount - $totalDayAdvanceLeaveRequest, 0);
                    $totalDayAdvanceLeaveRequest -= $timeoffRegulationMonth->amount;
                    $timeoffRegulationMonth->amount = $amount;
                } elseif ($timeoffRegulationMonth->month < $startMonth || ($timeoffRegulationMonth->month == $startMonth && date('d') >= $timeoffRegulation->cut_off_date)) {
                    $timeoffRegulationMonth->amount = 0;
                }
                // handle total amount untuk bulan $startMonth
                // elseif($timeoffRegulationMonth->month == $startMonth) {
                // }
            }
        }

        $user->timeoffRegulationMonths()->createMany($timeoffRegulationMonths->toArray());
        $advancedLeaveRequest->update(['data' => ['new' => $timeoffRegulationMonths, 'old' => $oldTimeoffRegulationMonths]]);
    }

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
