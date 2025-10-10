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
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReimbursementCategoryService extends BaseService implements ReimbursementCategoryServiceInterface
{
    public function __construct(protected ReimbursementCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function addUsers(ReimbursementCategory $reimbursementCategory, Collection $data)
    {
        $reimbursementCategory->users()->detach($data->pluck('user_id'));
        $reimbursementCategory->users()->attach($data);
    }

    public function editUsers(ReimbursementCategory $reimbursementCategory, array $data)
    {
        $reimbursementCategory->users()->where('id', $data['user_id'])->update(['limit_amount' => $data['limit_amount']]);
    }

    public function deleteUsers(ReimbursementCategory $reimbursementCategory, array $userIds)
    {
        $reimbursementCategory->users()->detach($userIds);
    }

    /**
     * Get the limit amount for a user on a reimbursement category.
     *
     * @param ReimbursementCategory|int $reimbursementCategoryId
     * @param User|int $user
     * @return int
     * @throws UnprocessableEntityHttpException
     */
    public function getLimitAmount(ReimbursementCategory|int $reimbursementCategoryId, User|int $user): int
    {
        if ($reimbursementCategoryId instanceof ReimbursementCategory) {
            $reimbursementCategoryId = $reimbursementCategoryId->id;
        }

        if (is_int($user)) {
            $user = User::select('id')->where('id', $user)->firstOrFail();
        }

        $userReimbursementCategory = $user->reimbursementCategories()->where('reimbursement_category_id', $reimbursementCategoryId)->first();

        if (!$userReimbursementCategory && !$userReimbursementCategory->pivot?->limit_amount) {
            throw new UnprocessableEntityHttpException('User does not have this Reimbursement Category');
        }

        return $userReimbursementCategory->pivot->limit_amount;
    }

    /**
     * Calculate the start and end dates for a reimbursement category based on the requested date.
     *
     * If the reimbursement category has a monthly period type, the start and end dates are determined
     * by the payroll settings of the associated company. Otherwise, it defaults to the entire year of
     * the requested date.
     *
     * @param ReimbursementCategory $reimbursementCategory The reimbursement category to evaluate.
     * @param string $requestedDate The date for which to calculate the start and end dates.
     * @return array An array containing the start and end dates as strings in 'Y-m-d' format.
     */
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
