<?php

namespace App\Http\Services\ReimbursementCategory;

use App\Enums\ReimbursementPeriodType;
use App\Http\Services\BaseService;
use App\Interfaces\Repositories\ReimbursementCategory\ReimbursementCategoryRepositoryInterface;
use App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface;
use App\Models\PayrollSetting;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Carbon\Carbon;

class ReimbursementCategoryService extends BaseService implements ReimbursementCategoryServiceInterface
{
    public function __construct(protected ReimbursementCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getLimitAmount(ReimbursementCategory|int $reimbursementCategory, User|int $user): int
    {
        if (is_int($reimbursementCategory)) {
            $reimbursementCategory = $this->findById($reimbursementCategory);
        }

        if (is_int($user)) {
            $user = User::select('id')->where('id', $user)->firstOrFail();
        }

        $limitAmount = $reimbursementCategory->limit_amount;

        $userReimbursementCategory = $user->reimbursementCategories()->where('reimbursement_category_id', $reimbursementCategory->id)->first();
        if ($userReimbursementCategory && $userReimbursementCategory->pivot?->limit_amount) {
            $limitAmount = $userReimbursementCategory->pivot->limit_amount;
        }

        return $limitAmount;
    }

    public function getStartEndDate(ReimbursementCategory $reimbursementCategory, string $requestedDate): array
    {
        $startDate = date('Y-01-01', strtotime($requestedDate));
        $endDate = date('Y-12-t', strtotime($requestedDate));
        if ($reimbursementCategory->period_type->is(ReimbursementPeriodType::MONTHLY)) {
            $payrollSetting = PayrollSetting::select('cut_off_attendance_start_date', 'cut_off_attendance_end_date')->where('company_id', $reimbursementCategory->company_id)->first();
            if ($payrollSetting) {
                $date = self::generateDate($payrollSetting->cut_off_attendance_start_date, $payrollSetting->cut_off_attendance_end_date, date('m-Y', strtotime($requestedDate)));
                $startDate = $date['start']->format('Y-m-d');
                $endDate = $date['end']->format('Y-m-d');
                // $startDate = date('Y-m-' . $payrollSetting->cut_off_attendance_start_date, strtotime($requestedDate));
                // $endDate = date('Y-m-' . $payrollSetting->cut_off_attendance_end_date, strtotime($requestedDate));
            }
        }

        return [$startDate, $endDate];
    }

    public static function generateDate(string $startDate, string $endDate, string $period, bool $isSubMonth = false): array
    {
        $start = Carbon::parse($startDate . '-' . $period);
        $end = Carbon::parse($endDate . '-' . $period);
        if ($start->greaterThan($end)) {
            $start->subMonthNoOverflow();
        }

        $endBase = Carbon::parse("01-{$period}"); // ambil awal bulan
        $daysInMonth = $endBase->daysInMonth;

        if ((int) $endDate > $daysInMonth) {
            $end = $endBase->endOfMonth();
        } else {
            $end = Carbon::parse("{$endDate}-{$period}");
        }

        if ($isSubMonth) {
            $start->subMonthNoOverflow();
            $end->subMonthNoOverflow();

            if ((int) $endDate > $daysInMonth) {
                $end->endOfMonth();
            }
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
}
