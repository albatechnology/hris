<?php

namespace App\Http\Services\Payroll;

use App\Enums\ApprovalStatus;
use App\Enums\CountrySettingKey;
use App\Enums\JaminanPensiunCost;
use App\Enums\OvertimeSetting;
use App\Enums\PaidBy;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Enums\ProrateSetting;
use App\Enums\RunPayrollStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Helpers\AttendanceHelper;
use App\Http\DTO\Payroll\RunPayrollDTO;
use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Payroll\RunPayrollRepositoryInterface;
use App\Interfaces\Services\Payroll\RunPayrollServiceInterface;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Loan;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\UpdatePayrollComponentDetail;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\FormulaService;
use App\Services\OvertimeService;
use App\Services\RunPayrollService as ServicesRunPayrollService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RunPayrollService extends BaseService implements RunPayrollServiceInterface
{
    public function __construct(protected RunPayrollRepositoryInterface $repository)
    {
        parent::__construct($repository);
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

    public function store(RunPayrollDTO $dto): RunPayroll|JsonResponse
    {

        return $this->execute($dto);


        // return $this->baseRepository->create($dto);
    }

    private function execute(RunPayrollDTO $dto): RunPayroll | Exception | JsonResponse
    {
        $payrollSetting = PayrollSetting::with('company')
            ->whereCompany($dto->company_id)
            ->whenBranch($dto->branch_id ?? null)
            ->first();

        $cutOffAttendance = self::generateDate($payrollSetting->cut_off_attendance_start_date, $payrollSetting->cut_off_attendance_end_date, $dto->period, $payrollSetting->is_attendance_pay_last_month);
        $payrollDate = self::generateDate($payrollSetting->payroll_start_date, $payrollSetting->payroll_end_date, $dto->period);

        $eligibleUsersCount = User::query()
            ->when(count($dto->array_user_ids), fn($q) => $q->whereIn('id', $dto->array_user_ids))
            ->whenBranch($dto->branch_id)
            ->whereDoesntHave('runPayrollUser', function ($q) use ($dto) {
                $q->whereHas('runPayroll', function ($rp) use ($dto) {
                    $rp->where('period', $dto->period)
                        ->where('company_id', $dto->company_id)
                        ->when($dto->branch_id, fn($b) => $b->where('branch_id', $dto->branch_id))
                        ->where('status', RunPayrollStatus::RELEASE);
                });
            })
            ->where('company_id', $dto->company_id)
            ->whereDate('join_date', '<=', $payrollDate['end'])
            ->has('payrollinfo')
            ->count();

        if ($eligibleUsersCount === 0) {
            return response()->json([
                'success' => false,
                'message' => count($dto->array_user_ids) > 0
                    ? 'All selected users have already been released for this period.'
                    : 'No eligible users found for this payroll period. All users have already been released.',
                'data' => null
            ], 422);
        }

        // $request = array_merge($request, [
        //     'cut_off_start_date' => $cutOffAttendance['start'],
        //     'cut_off_end_date' => $cutOffAttendance['end'],
        //     'payroll_start_date' => $payrollDate['start'],
        //     'payroll_end_date' => $payrollDate['end'],
        // ]);

        DB::beginTransaction();
        try {
            // $runPayroll = self::createRunPayroll($request);
            $runPayroll = auth('sanctum')->user()->runPayrolls()->create([
                'branch_id' => $dto->branch_id ?? null,
                'company_id' => $dto->company_id,
                'period' => $dto->period,
                'payment_schedule' => $dto->payment_schedule,
                'status' => RunPayrollStatus::REVIEW,
                'cut_off_start_date' => $cutOffAttendance['start'],
                'cut_off_end_date' => $cutOffAttendance['end'],
                'payroll_start_date' => $payrollDate['start'],
                'payroll_end_date' => $payrollDate['end'],
            ]);

            $runPayrollDetail = $this->createDetails($payrollSetting, $runPayroll, $dto);

            // check if there's json error response
            if (!$runPayrollDetail->getData()?->success) {
                DB::rollBack();
                return response()->json($runPayrollDetail->getData());
            }

            DB::commit();

            return $runPayroll;
        } catch (Exception $e) {
            DB::rollBack();

            throw new Exception($e);
        }
    }

    private function assignUser(RunPayroll $runPayroll, string|int $userId): RunPayrollUser
    {
        return $runPayroll->users()->create(['user_id' => $userId]);
    }

    /**
     * create run payroll user components
     *
     * @param  RunPayrollUser   $runPayrollUser
     * @param  int              $payrollComponentId
     * @param  int|float        $amomunt
     * @param  bool             $isEditable
     * @return RunPayrollUserComponent
     */
    public function createComponent(RunPayrollUser $runPayrollUser, PayrollComponent $payrollComponent, int|float $amount = 0, array|null $context = null, ?bool $isEditable = true): RunPayrollUserComponent
    {
        return $runPayrollUser->components()->create([
            'payroll_component_id' => $payrollComponent->id,
            'amount' => $amount,
            'is_editable' => $isEditable,
            'payroll_component' => $payrollComponent,
            'context' => $context,
        ]);
    }

    /**
     * Calculates the amount of a payroll component based on its period type.
     *
     * @param PayrollComponent $payrollComponent The payroll component to calculate.
     * @param int|float $amount The initial amount of the component. Default is 0.
     * @param int $cutoffDiffDay The number of days between the cutoff start and end dates. Default is 0.
     * @param RunPayrollUser|null $runPayrollUser The run payroll user associated with the component. Default is null.
     * @return int|float The calculated amount of the component.
     */
    public function calculatePayrollComponentPeriodType(PayrollComponent $payrollComponent, int|float $amount = 0, int $cutoffDiffDay = 0, ?RunPayrollUser $runPayrollUser = null, ?UpdatePayrollComponentDetail $updatePayrollComponentDetail = null): int|float
    {
        if ($payrollComponent->category->is(PayrollComponentCategory::ALPA)) {
            return $amount;
        }

        switch ($payrollComponent->period_type) {
            case PayrollComponentPeriodType::DAILY:
                // rate_amount * cutoff diff days
                // if (!$payrollComponent->formulas) $amount = $amount * $cutoffDiffDay;
                $amount = $amount * $cutoffDiffDay;
                break;
            case PayrollComponentPeriodType::MONTHLY:
                $amount = $amount;

                break;
            case PayrollComponentPeriodType::ONE_TIME:
                $checkOneTime = $runPayrollUser->user->oneTimePayrollComponents()
                    ->where('payroll_component_id', $payrollComponent->id)
                    ->whereHas(
                        'runPayroll',
                        fn($q) => $q->release()
                            ->when($updatePayrollComponentDetail, function ($q) use ($updatePayrollComponentDetail) {
                                $startDate = $updatePayrollComponentDetail->updatePayrollComponent->effective_date;
                                $endDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ?? null;
                                return $q->when(
                                    !$endDate,
                                    function ($q) use ($startDate) {
                                        return $q->whereDate('payroll_start_date', '>', $startDate);
                                    },
                                    function ($q) use ($startDate, $endDate) {
                                        return $q->whereDate('payroll_start_date', '>=', $startDate)
                                            ->whereDate('payroll_start_date', '<=', $endDate);
                                    }
                                );
                            })
                    )
                    ->exists();

                if ($checkOneTime) {
                    $amount = 0;
                } else {
                    $runPayrollUser->user->oneTimePayrollComponents()->create([
                        'payroll_component_id' => $payrollComponent->id,
                        'run_payroll_id' => $runPayrollUser->run_payroll_id,
                    ]);
                    $amount = $amount;
                }

                break;
            default:
                //

                break;
        }

        return $amount;
    }

    public function isFirstTimePayroll(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return RunPayrollUser::query()->where('user_id', $userId)
            ->whereHas('runPayroll', fn($q) => $q->release())
            ->limit(1)
            ->doesntExist();
    }

    public function calculateProrateTotalDays(int $totalWorkingDays, Carbon $startDate, Carbon $endDate, bool $isSubOneDay = false): int
    {
        // if ($isSubOneDay) {
        //     $period = CarbonPeriod::between($startDate, $endDate->subDays(2));
        // } else {
        //     $period = CarbonPeriod::between($startDate, $endDate);
        // }
        $period = CarbonPeriod::between($startDate, $endDate);

        if ($totalWorkingDays > 21) {
            $wd = collect($period)->filter(function (Carbon $tanggal) {
                return !$tanggal->isSunday(); // sunday is not included
            })->count();
        } else {
            $wd = collect($period)->filter(function (Carbon $tanggal) {
                return !$tanggal->isWeekend(); // weekends is not included
            })->count();
        }

        if ($isSubOneDay) return $wd -= 1;

        return max($wd, 0);
    }

    public function prorate(int|float $basicAmount, int|float $updatePayrollComponentAmount, int $totalWorkingDays, Carbon $startDate, Carbon $endDate, Carbon $startEffectiveDate, Carbon|null $endEffectiveDate, bool $isDebug = false): int|float
    {
        // effective_date is between period
        if ($startEffectiveDate->between($startDate, $endDate)) {
            // jika terdapat end_date
            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($endDate)) {
                $totalDaysFromStartDateToStartEffectiveDate = $this->calculateProrateTotalDays($totalWorkingDays, $startDate, $startEffectiveDate, true);
                $startSalary = ($totalDaysFromStartDateToStartEffectiveDate / $totalWorkingDays) * $basicAmount;

                $totalDaysFromStartEffectiveDateToEndEffectiveDate = $this->calculateProrateTotalDays($totalWorkingDays, $startEffectiveDate, $endEffectiveDate);
                $middleSalary = ($totalDaysFromStartEffectiveDateToEndEffectiveDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $endSalary = 0;
                if ($endEffectiveDate->lessThan($endDate)) {
                    $totalDaysFromEndEffectiveDateToEndDate = $this->calculateProrateTotalDays($totalWorkingDays, $endEffectiveDate, $endDate, true);
                    $endSalary = ($totalDaysFromEndEffectiveDateToEndDate / $totalWorkingDays) * $basicAmount;
                }

                $basicAmount = $startSalary + $middleSalary + $endSalary;
            } else {
                // NORMAL CALCULATION
                $totalDaysFromStartDateToStartEffectiveDate = $this->calculateProrateTotalDays($totalWorkingDays, $startDate, $startEffectiveDate, true);
                $startSalary = ($totalDaysFromStartDateToStartEffectiveDate / $totalWorkingDays) * $basicAmount;
                // $totalDaysFromStartEffectiveDateToEndDate = $this->calculateProrateTotalDays($totalWorkingDays, $startEffectiveDate, $endDate);
                $totalDaysFromStartEffectiveDateToEndDate = $totalWorkingDays - $totalDaysFromStartDateToStartEffectiveDate;
                $endSalary = ($totalDaysFromStartEffectiveDateToEndDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $basicAmount = $startSalary + $endSalary;
            }
        } else {
            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($endDate)) {
                $totalDaysFromStartDateToEndEffectiveDate = $this->calculateProrateTotalDays($totalWorkingDays, $startDate, $endEffectiveDate);
                $startSalary = ($totalDaysFromStartDateToEndEffectiveDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                // $totalDaysFromEndEffectiveDateToEndDate = $this->calculateProrateTotalDays($totalWorkingDays, $endEffectiveDate, $endDate, true);
                $totalDaysFromEndEffectiveDateToEndDate = $totalWorkingDays - $totalDaysFromStartDateToEndEffectiveDate;
                $endSalary = ($totalDaysFromEndEffectiveDateToEndDate / $totalWorkingDays) * $basicAmount;

                $basicAmount = $startSalary + $endSalary;
            } else {
                $basicAmount = $updatePayrollComponentAmount;
            }
        }

        return $basicAmount;
    }

    public function newProrate(int|float $basicAmount, int|float $updatePayrollComponentAmount, array $dataTotalAttendance, Carbon $startDate, Carbon $endDate, Carbon $startEffectiveDate, Carbon|null $endEffectiveDate): int|float
    {
        $endDate->startOfDay();

        $totalPresent = $dataTotalAttendance['total_present'];
        $totalPresentDates = collect($dataTotalAttendance['total_present_dates']);
        $totalWorkingDays = $dataTotalAttendance['total_working_days'];
        // $totalWorkingDaysDates = collect($dataTotalAttendance['total_working_days_dates']);
        if ($startEffectiveDate->between($startDate, $endDate)) {
            // ex: $startEffective = 2025-09-15 $endEffectiveDate = 2025-09-30, $startDate = 2025-09-01 $endDate = 2025-09-30

            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($endDate)) {

                if ($startEffectiveDate->equalTo($startDate)) {
                    // $endEffectiveDate not null
                    // ex: $startEffective = 2025-09-01 $endEffectiveDate = 2025-09-30 or less, $startDate = 2025-09-01 $endDate = 2025-09-30
                    // ex: $startEffective = 2025-09-01 $endEffectiveDate = 2025-09-25, $startDate = 2025-09-01 $endDate = 2025-09-30

                    $totalPresentFromStartEffectiveDateToEndEffectiveDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startEffectiveDate, $endEffectiveDate))->count();
                    $startAmount = $totalPresentFromStartEffectiveDateToEndEffectiveDate / $totalWorkingDays * $updatePayrollComponentAmount;

                    $totalPresentFromEndEffectiveDateTpEndDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($endEffectiveDate, $endDate))->count() - 1;
                    $endAmount = $totalPresentFromEndEffectiveDateTpEndDate / $totalWorkingDays * $basicAmount;

                    $basicAmount = $startAmount + $endAmount;
                } else {
                    // $endEffectiveDate not null
                    // ex: $startEffective = 2025-09-15 $endEffectiveDate = 2025-09-30 or less, $startDate = 2025-09-01 $endDate = 2025-09-30
                    // ex: $startEffective = 2025-09-10 $endEffectiveDate = 2025-09-20, $startDate = 2025-09-01 $endDate = 2025-09-30

                    $totalPresentFromStartDateToStartEffectiveDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startDate, $startEffectiveDate))->count() - 1;
                    $startAmount = $totalPresentFromStartDateToStartEffectiveDate / $totalWorkingDays * $basicAmount;

                    $totalPresentFromStartEffectiveDateToEndEffectiveDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startEffectiveDate, $endEffectiveDate))->count();
                    $middleAmount = $totalPresentFromStartEffectiveDateToEndEffectiveDate / $totalWorkingDays * $updatePayrollComponentAmount;

                    $endAmount = 0;
                    if ($endEffectiveDate->lessThan($endDate)) {
                        $totalPresentFromEndEffectiveDateToEndDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startEffectiveDate, $endEffectiveDate))->count();
                        $endAmount = $totalPresentFromEndEffectiveDateToEndDate / $totalWorkingDays * $basicAmount;
                    }

                    $basicAmount = $startAmount + $middleAmount + $endAmount;
                }
            } else {
                // $endEffectiveDate is null or more than $endDate
                // ex: $startEffective = 2025-09-15 $endEffectiveDate is null or more than $endDate, $startDate = 2025-09-01 $endDate = 2025-09-30
                $totalPresentFromStartDateToStartEffectiveDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startDate, $startEffectiveDate))->count() - 1;
                $totalPresentFromStartEffectiveDateToEndEffectiveDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startEffectiveDate, $endDate))->count();

                $startAmount = $totalPresentFromStartDateToStartEffectiveDate / $totalWorkingDays * $basicAmount;
                $endAmount = $totalPresentFromStartEffectiveDateToEndEffectiveDate / $totalWorkingDays * $updatePayrollComponentAmount;

                $basicAmount = $startAmount + $endAmount;
            }
        } elseif ($startEffectiveDate->lessThan($startDate)) {
            if ($endEffectiveDate && $endEffectiveDate->between($startDate, $endDate)) {
                // ex: $startEffective = 2025-08-31 or less $endEffectiveDate = 2025-09-30, $startDate = 2025-09-01 $endDate = 2025-09-30
                // ex: $startEffective = 2025-08-01 $endEffectiveDate = 2025-09-20, $startDate = 2025-09-01 $endDate = 2025-09-30

                $totalPresentFromStartDateToEndEffectiveDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($startDate, $endEffectiveDate))->count();
                $startAmount = $totalPresentFromStartDateToEndEffectiveDate / $totalWorkingDays * $updatePayrollComponentAmount;

                $totalPresentFromEndEffectiveDateTpEndDate = $totalPresentDates->filter(fn($date) => Carbon::parse($date)->betweenIncluded($endEffectiveDate, $endDate))->count() - 1;
                $endAmount = $totalPresentFromEndEffectiveDateTpEndDate / $totalWorkingDays * $basicAmount;

                $basicAmount = $startAmount + $endAmount;
            } else {
                // ex: $startEffective = 2025-08-15 $endEffectiveDate = 2025-10-15, $startDate = 2025-09-01 $endDate = 2025-09-30
                // or $endEffectiveDate = null
                $basicAmount = $totalPresent / $totalWorkingDays * $updatePayrollComponentAmount;
            }
        }

        return $basicAmount;
    }

    /**
     * create run payroll details
     *
     * @param  PayrollSetting   $payrollSetting
     * @param  RunPayroll   $runPayroll
     * @param  RunPayrollDTO      $dto
     * @return JsonResponse
     */
    private function createDetails(PayrollSetting $payrollSetting, RunPayroll $runPayroll, RunPayrollDTO $dto): JsonResponse
    {
        $company = $payrollSetting->company;

        $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
        $max_jp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::JP_MAXIMUM_SALARY)?->value;

        $users = User::query()
            ->when(count($dto->array_user_ids), fn($q) => $q->whereIn('id', $dto->array_user_ids))
            ->whenBranch($runPayroll->branch_id)
            ->whereDoesntHave('runPayrollUser', function ($q) use ($runPayroll) {
                $q->whereHas('runPayroll', function ($rp) use ($runPayroll) {
                    $rp->where('period', $runPayroll->period)
                        ->where('company_id', $runPayroll->company_id)
                        ->when($runPayroll->branch_id, fn($b) => $b->where('branch_id', $runPayroll->branch_id))
                        ->where('status', RunPayrollStatus::RELEASE);
                });
            })
            ->where('company_id', $runPayroll->company_id)
            ->whereDate('join_date', '<=', $runPayroll->payroll_end_date)
            ->has('payrollInfo')
            ->with('payrollInfo')
            ->get();

        $allPayrollComponents = PayrollComponent::whereCompany($runPayroll->company_id)->whenBranch($runPayroll->branch_id)->get();

        $basicSalaryComponent = $allPayrollComponents->where('category', PayrollComponentCategory::BASIC_SALARY)->firstOrFail();
        $reimbursementComponent = $allPayrollComponents->where('category', PayrollComponentCategory::REIMBURSEMENT)->first();
        $alpaComponent = $allPayrollComponents->where('category', PayrollComponentCategory::ALPA)->first();
        $loanComponent = $allPayrollComponents->where('category', PayrollComponentCategory::LOAN)->first();
        $insuranceComponent = $allPayrollComponents->where('category', PayrollComponentCategory::INSURANCE)->first();
        $overtimePayrollComponent = $allPayrollComponents->where('category', PayrollComponentCategory::OVERTIME)->first();
        $bpjsKesehatanFamilyComponent = $allPayrollComponents->where('category', PayrollComponentCategory::BPJS_KESEHATAN_FAMILY)->first();

        $payrollComponents =  PayrollComponent::whereCompany($runPayroll->company_id)->whenBranch($runPayroll->branch_id)->whereNotDefault()->get();
        $bpjsPayrollComponents =  PayrollComponent::whereCompany($runPayroll->company_id)->whenBranch($runPayroll->branch_id)->whereBpjs()->get();

        // calculate for each user
        // foreach ($userIds as $userId) {
        foreach ($users as $user) {
            $cutOffStartDate = $runPayroll->cut_off_start_date;
            $cutOffEndDate = $runPayroll->cut_off_end_date;
            $startDate = $runPayroll->payroll_start_date;
            $endDate = $runPayroll->payroll_end_date;

            // /** @var \App\Models\User $user */
            // $user = User::where('id', $userId)->has('payrollInfo')->with('payrollInfo')->first();
            // if (!$user) continue;
            $resignDate = null;

            // if ($user->join_date) {
            //     $joinDate = Carbon::parse($user->join_date);
            //     if ($joinDate->greaterThan($endDate)) {
            //         continue;
            //     }
            // }

            if ($user->resign_date) {
                $resignDate = Carbon::parse($user->resign_date);
                // If user resigned before the payroll period starts, only process active updates (e.g., severance)
                if ($resignDate->lessThan($startDate)) {
                    $updatePayrollComponentDetails = UpdatePayrollComponentDetail::with('updatePayrollComponent')
                        ->where('user_id', $user->id)
                        ->whereHas(
                            'updatePayrollComponent',
                            fn($q) => $q->whereCompany($runPayroll->company_id)
                                ->whenBranch($runPayroll->branch_id)
                                ->whereActive($startDate, $endDate)
                        )
                        ->orderByDesc('id')
                        ->get();
                    // dd($updatePayrollComponentDetails);
                    // If there are no active updates for this period, skip the user
                    if ($updatePayrollComponentDetails->isEmpty()) {
                        continue;
                    }

                    // Create the payroll user entry so we can record severance/adjustments
                    $runPayrollUser = self::assignUser($runPayroll, $user->id);

                    // Add each updated component (e.g., severance pay) for the user
                    foreach ($updatePayrollComponentDetails as $upd) {
                        $component = PayrollComponent::tenanted()
                            ->whereCompany($runPayroll->company_id)
                            ->whenBranch($runPayroll->branch_id)
                            ->where('id', $upd->payroll_component_id)
                            ->first();

                        if (!$component) continue;

                        // For ONE_TIME components, this prevents double payment across released runs
                        $amount = self::calculatePayrollComponentPeriodType($component, $upd->new_amount, 0, $runPayrollUser, $upd);
                        self::createComponent($runPayrollUser, $component, $amount);
                    }

                    // Refresh totals and move to next user
                    self::refreshRunPayrollUser($runPayrollUser);
                    continue;
                }
            }

            $runPayrollUser = $this->assignUser($runPayroll, $user->id);

            $userBasicSalary = $user->payrollInfo?->basic_salary;

            $isTaxable = $user->payrollInfo?->tax_salary->is(TaxSalary::TAXABLE) ?? true;

            $isFirstTimePayroll = $this->isFirstTimePayroll($user);
            $joinDate = Carbon::parse($user->join_date);

            if ($isFirstTimePayroll && $joinDate->between($cutOffStartDate, $cutOffEndDate)) {
                $cutOffStartDate = $joinDate;
                // $cutOffEndDate = $cutOffEndDate;
                $dataTotalAttendance = AttendanceHelper::getTotalAttendanceForPayroll($payrollSetting, $user, $runPayroll->cut_off_start_date, $cutOffEndDate, $joinDate);
                $totalPresent = $dataTotalAttendance['total_present'];
                $totalWorkingDays = $dataTotalAttendance['total_working_days'];

                // $userBasicSalary = $totalPresent / $totalWorkingDays * $userBasicSalary;
            } elseif ($isFirstTimePayroll && $joinDate->between($startDate, $endDate)) {
                $cutOffStartDate = $joinDate;
                $cutOffEndDate = $endDate;

                $dataTotalAttendance = AttendanceHelper::getTotalAttendanceForPayroll($payrollSetting, $user, $startDate, $cutOffEndDate, $joinDate);
                $totalPresent = $dataTotalAttendance['total_present'];
                $totalWorkingDays = $dataTotalAttendance['total_working_days'];
            } elseif ($resignDate && $resignDate->between($startDate, $endDate)) {
                $cutOffStartDate = $startDate;
                $cutOffEndDate = $resignDate;
                $dataTotalAttendance = AttendanceHelper::getTotalAttendanceForPayroll(
                    $payrollSetting,
                    $user,
                    $cutOffStartDate,
                    $cutOffEndDate
                );
            dump($dataTotalAttendance);
                $totalWorkingDays = AttendanceService::getTotalWorkingDays($user, $cutOffStartDate, $cutOffEndDate);
                $totalPresent = AttendanceService::getTotalAttend($user, $cutOffStartDate, $cutOffEndDate);
            dump($totalWorkingDays);
            dd($totalPresent);

                $userBasicSalary = ($userBasicSalary / $totalWorkingDays) * $totalPresent;
            } else {
                // $totalWorkingDays = AttendanceService::getTotalWorkingDays($user, $cutOffStartDate, $cutOffEndDate);
                // $totalWorkingDays = AttendanceService::getTotalAttend($user, $cutOffStartDate, $cutOffEndDate);
                $dataTotalAttendance = AttendanceHelper::getTotalAttendanceForPayroll($payrollSetting, $user, $cutOffStartDate, $cutOffEndDate);
                $totalPresent = $dataTotalAttendance['total_present'];
                $totalWorkingDays = $dataTotalAttendance['total_working_days'];
            }
            dd($user->toArray());

            $updatePayrollComponentDetails = UpdatePayrollComponentDetail::with('updatePayrollComponent')
                ->where('user_id', $user->id)
                ->whereHas(
                    'updatePayrollComponent',
                    fn($q) => $q->whereCompany($runPayroll->company_id)
                        ->whenBranch($runPayroll->branch_id)
                        ->whereActive($startDate, $endDate)
                )
                ->orderByDesc('id')->get();

            /**
             * first, calculate basic salary. for now basic salary component is required
             */
            // $basicSalaryComponent = PayrollComponent::tenanted()
            //     ->where('company_id', $runPayroll->company_id)
            //     ->whenBranch($runPayroll->branch_id)
            //     ->where('category', PayrollComponentCategory::BASIC_SALARY)->firstOrFail();

            $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $basicSalaryComponent->id)->first();
            // if ($isFirstTimePayroll && $joinDate->between($cutOffStartDate, $cutOffEndDate)) {
            //     $userBasicSalary = $totalWorkingDays / $user->payrollInfo->total_working_days * $userBasicSalary;
            // }
            if ($updatePayrollComponentDetail) {
                $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                // end_date / endEffectiveDate can be null
                $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                $userBasicSalary = $this->newProrate($userBasicSalary, $updatePayrollComponentDetail->new_amount, $dataTotalAttendance, $startDate, $endDate, $startEffectiveDate, $endEffectiveDate);
            } else {
                // if ($basicSalaryComponent->is_prorate) {
                //     $userBasicSalary = $this->newProrate(0, $userBasicSalary, $dataTotalAttendance, $cutOffStartDate, $cutOffEndDate, $cutOffStartDate, $cutOffEndDate);
                // }
            }

            $amount = $this->calculatePayrollComponentPeriodType($basicSalaryComponent, $userBasicSalary, $totalWorkingDays, $runPayrollUser);
            $this->createComponent($runPayrollUser, $basicSalaryComponent, $amount);

            /**
             * five, calculate reimbursement
             */
            // $reimbursementComponent = PayrollComponent::tenanted()
            //     ->whereCompany($runPayroll->company_id)
            //     ->whenBranch($runPayroll->branch_id)
            //     ->where('category', PayrollComponentCategory::REIMBURSEMENT)->first();

            if ($reimbursementComponent) {
                $amount = app(\App\Http\Services\Reimbursement\ReimbursementService::class)->getTotalReimbursementTaken(userId: $user, startDate: $cutOffStartDate, endDate: $cutOffEndDate);

                $this->createComponent($runPayrollUser, $reimbursementComponent, $amount);
            }
            // END

            /**
             * second, calculate payroll component where not default
             */
            // $payrollComponents = PayrollComponent::tenanted()
            //     ->where('id', 127)
            //     ->where('company_id', $runPayroll->company_id)
            //     ->whenBranch($runPayroll->branch_id)
            //     ->whereNotDefault()->get();

            $payrollComponents->each(function ($payrollComponent) use ($user, $dataTotalAttendance, $updatePayrollComponentDetails, $runPayrollUser,  $totalWorkingDays, $cutOffStartDate, $cutOffEndDate) {

                if ($payrollComponent->amount == 0 && count($payrollComponent->formulas)) {
                    $amount = FormulaService::calculate(user: $user, model: $payrollComponent, formulas: $payrollComponent->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);
                } else {
                    $amount = $payrollComponent->amount;
                }

                $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $payrollComponent->id)->first();
                if ($updatePayrollComponentDetail) {
                    $updatePayrollComponentAmount = $this->calculatePayrollComponentPeriodType($payrollComponent, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $runPayrollUser, $updatePayrollComponentDetail);

                    $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                    // end_date / endEffectiveDate can be null
                    $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                    if ($payrollComponent->is_prorate) {
                        $amount = $this->newProrate(0, $amount, $dataTotalAttendance, $cutOffStartDate, $cutOffEndDate, $cutOffStartDate, $cutOffEndDate);
                    } else {
                        $amount = $updatePayrollComponentDetail->new_amount;
                    }
                    // calculate prorate
                    // $amount = $this->newProrate(0, $updatePayrollComponentAmount, $dataTotalAttendance, $cutOffStartDate, $cutOffEndDate, $startEffectiveDate, $endEffectiveDate);

                    // $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                    // // end_date / endEffectiveDate can be null
                    // $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                    // calculate prorate
                    // $amount = $this->prorate($amount, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $cutOffStartDate, $cutOffEndDate, $startEffectiveDate, $endEffectiveDate, true);

                    // $amount = $this->calculatePayrollComponentPeriodType($payrollComponent, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $runPayrollUser, $updatePayrollComponentDetail);
                } else {
                    $amount = $this->calculatePayrollComponentPeriodType($payrollComponent, $amount, $totalWorkingDays, $runPayrollUser);
                    if ($payrollComponent->is_prorate) {
                        $amount = $this->newProrate(0, $amount, $dataTotalAttendance, $cutOffStartDate, $cutOffEndDate, $cutOffStartDate, $cutOffEndDate);
                    }
                }
                $this->createComponent($runPayrollUser, $payrollComponent, $amount);
            });
            // END

            /**
             * third, calculate alpa
             */
            if ($user->payrollInfo?->is_ignore_alpa == false && !$joinDate->between($cutOffStartDate, $cutOffEndDate) && (config('app.name') != 'SUNSHINE' || !$isFirstTimePayroll)) {
                // $alpaComponent = PayrollComponent::tenanted()
                //     ->where('company_id', $runPayroll->company_id)
                //     ->whenBranch($runPayroll->branch_id)
                //     ->where('category', PayrollComponentCategory::ALPA)->first();

                $totalWorkingDays = $dataTotalAttendance['total_working_days'];
                $totalAlpa = $totalWorkingDays - $dataTotalAttendance['total_present'];

                if ($alpaComponent && $totalAlpa > 0) {
                    $alpaUpdateComponent = $updatePayrollComponentDetails->where('payroll_component_id', $alpaComponent->id)->first();
                    if ($alpaUpdateComponent) {
                        $amount = $alpaUpdateComponent->new_amount;
                    } else {
                        // get total alpa di range tgl cuttoff
                        // potongan = (totalAlpa/totalHariKerja)*(basicSalary+SUM(allowance))
                        // $totalWorkingDays = ScheduleService::getTotalWorkingDaysInPeriod($user, $cutOffStartDate, $cutOffEndDate);
                        // $totalAlpa = AttendanceService::getTotalAlpa($user, $cutOffStartDate, $cutOffEndDate);
                        $totalAllowance = $runPayrollUser->components()->whereHas('payrollComponent', fn($q) => $q->where('type', PayrollComponentType::ALLOWANCE)->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY, PayrollComponentCategory::REIMBURSEMENT, PayrollComponentCategory::OVERTIME]))->sum('amount');
                        $amount = round(max(($totalAlpa / $totalWorkingDays) * ($userBasicSalary + $totalAllowance), 0));
                    }

                    $amount = $this->calculatePayrollComponentPeriodType($alpaComponent, $amount, $totalWorkingDays, $runPayrollUser);
                    $this->createComponent($runPayrollUser, $alpaComponent, $amount);
                }
            }
            // END

            /**
             * calculate LOAN
             */
            // $loanComponent = PayrollComponent::tenanted()
            //     ->where('company_id', $runPayroll->company_id)
            //     ->whenBranch($runPayroll->branch_id)
            //     ->where('category', PayrollComponentCategory::LOAN)->first();

            if ($loanComponent) {
                $whereHas = fn($q) => $q->whereNull('run_payroll_user_id')->where('payment_period_year', $startDate->format('Y'))->where('payment_period_month', $startDate->format('m'));
                $loans = Loan::where('user_id', $user->id)->whereLoan()->whereHas('details', $whereHas)->get(['id']);
                if ($loans->count()) {
                    $loans->load(['details' => $whereHas]);
                    $amount = $loans->sum(fn($loan) => $loan->details->sum('total'));

                    $this->createComponent($runPayrollUser, $loanComponent, $amount, [
                        "loans" => $loans->toArray()
                    ]);
                }
            }

            /**
             * calculate INSURANCE
             */
            // $insuranceComponent = PayrollComponent::tenanted()
            //     ->where('company_id', $runPayroll->company_id)
            //     ->whenBranch($runPayroll->branch_id)
            //     ->where('category', PayrollComponentCategory::INSURANCE)->first();

            if ($insuranceComponent) {
                $whereHas = fn($q) => $q->whereNull('run_payroll_user_id')->where('payment_period_year', $startDate->format('Y'))->where('payment_period_month', $startDate->format('m'));
                $insurances = Loan::where('user_id', $user->id)->whereInsurance()->whereHas('details', $whereHas)->get(['id']);
                if ($insurances->count()) {
                    $insurances->load(['details' => $whereHas]);
                    $amount = $insurances->sum(fn($loan) => $loan->details->sum('total'));

                    // $insurances->each(fn($loan) => $loan->details->each->update(['run_payroll_user_id' => $runPayrollUser->id]));

                    $this->createComponent($runPayrollUser, $insuranceComponent, $amount, [
                        "insurances" => $insurances->toArray()
                    ]);
                }
            }

            /**
             * fourth, calculate bpjs
             */
            if ($company->countryTable?->id == 1 && $user->userBpjs) {
                // $bpjsPayrollComponents = PayrollComponent::tenanted()
                //     ->whereCompany($runPayroll->company_id)
                //     ->whenBranch($runPayroll->branch_id)
                //     ->whereBpjs()->get();

                $isEligibleToCalculateBpjsKesehatan = false;
                if (
                    !empty($user->userBpjs->bpjs_kesehatan_no)
                    && !empty($user->userBpjs->bpjs_kesehatan_date)
                    && (
                        date('Y', strtotime($user->userBpjs->bpjs_kesehatan_date)) < date('Y', strtotime($runPayroll->payroll_start_date))
                        || (date('Y', strtotime($user->userBpjs->bpjs_kesehatan_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) && date('m', strtotime($user->userBpjs->bpjs_kesehatan_date)) <= date('m', strtotime($runPayroll->payroll_start_date)))
                    )
                ) {
                    $isEligibleToCalculateBpjsKesehatan = true;
                }

                $isEligibleToCalculateBpjsKetenagakerjaan = false;
                if (
                    !empty($user->userBpjs->bpjs_ketenagakerjaan_no)
                    && !empty($user->userBpjs->bpjs_ketenagakerjaan_date)
                    && (
                        date('Y', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) < date('Y', strtotime($runPayroll->payroll_start_date))
                        || (date('Y', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) && date('m', strtotime($user->userBpjs->bpjs_ketenagakerjaan_date)) <= date('m', strtotime($runPayroll->payroll_start_date)))
                    )
                ) {
                    $isEligibleToCalculateBpjsKetenagakerjaan = true;
                }

                // calculate bpjs
                // init bpjs variable
                $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                $current_upahBpjsKetenagakerjaan = $user->userBpjs->upah_bpjs_ketenagakerjaan;
                if ($current_upahBpjsKetenagakerjaan > $max_jp) $current_upahBpjsKetenagakerjaan = $max_jp;

                // bpjs kesehatan
                $company_percentageBpjsKesehatan = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE)?->value;

                $employee_percentageBpjsKesehatan = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE)?->value;
                if (!$isTaxable) {
                    $company_percentageBpjsKesehatan += $employee_percentageBpjsKesehatan;
                    $employee_percentageBpjsKesehatan = 0;
                }

                $company_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($company_percentageBpjsKesehatan / 100);

                $employee_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($employee_percentageBpjsKesehatan / 100);
                if ($user->userBpjs->bpjs_kesehatan_cost->is(PaidBy::COMPANY)) {
                    $company_totalBpjsKesehatan += $employee_totalBpjsKesehatan;
                    $employee_totalBpjsKesehatan = 0;
                }

                // jkk
                $company_percentageJkk = $company->jkk_tier->getValue() ?? 0;
                $company_totalJkk = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkk / 100);

                // jkm
                $company_percentageJkm = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JKM_PERCENTAGE)?->value;
                $company_totalJkm = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkm / 100);

                // jht
                $company_percentageJht = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JHT_PERCENTAGE)?->value;
                $employee_percentageJht = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE)?->value;
                if (!$isTaxable) {
                    $company_percentageJht += $employee_percentageJht;
                    $employee_percentageJht = 0;
                }

                $company_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJht / 100);
                $employee_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($employee_percentageJht / 100);
                if ($user->userBpjs->jht_cost->is(PaidBy::COMPANY)) {
                    $company_totalJht += $employee_totalJht;
                    $employee_totalJht = 0;
                }

                // jp
                $company_totalJp = 0;
                $employee_totalJp = 0;
                if (!$user->userBpjs->jaminan_pensiun_cost->is(JaminanPensiunCost::NOT_PAID)) {
                    $company_percentageJp = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JP_PERCENTAGE)?->value;

                    $employee_percentageJp = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JP_PERCENTAGE)?->value;
                    if (!$isTaxable) {
                        $company_percentageJp += $employee_percentageJp;
                        $employee_percentageJp = 0;
                    }

                    $company_totalJp = $current_upahBpjsKetenagakerjaan * ($company_percentageJp / 100);

                    $employee_totalJp = $current_upahBpjsKetenagakerjaan * ($employee_percentageJp / 100);

                    if ($user->userBpjs->jaminan_pensiun_cost->is(JaminanPensiunCost::COMPANY)) {
                        $company_totalJp += $employee_totalJp;
                        $employee_totalJp = 0;
                    }
                }

                foreach ($bpjsPayrollComponents as $bpjsPayrollComponent) {
                    $amount = 0;
                    if ($isEligibleToCalculateBpjsKesehatan) {
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_BPJS_KESEHATAN)) $amount = $company_totalBpjsKesehatan;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN)) $amount = $employee_totalBpjsKesehatan;
                    }

                    if ($isEligibleToCalculateBpjsKetenagakerjaan) {
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKK)) $amount = $company_totalJkk;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKM)) $amount = $company_totalJkm;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JHT)) $amount = $company_totalJht;
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JHT)) $amount = $employee_totalJht;

                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JP)) $amount = $company_totalJp;
                        if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JP)) $amount = $employee_totalJp;
                    }

                    $amount = $this->calculatePayrollComponentPeriodType($bpjsPayrollComponent, $amount, $totalWorkingDays, $runPayrollUser);

                    $this->createComponent($runPayrollUser, $bpjsPayrollComponent, $amount);
                }
            }
            // END

            /**
             * five, calculate overtime
             */
            // $overtimePayrollComponent = PayrollComponent::tenanted()
            //     ->whereCompany($runPayroll->company_id)
            //     ->whenBranch($runPayroll->branch_id)
            //     ->where('category', PayrollComponentCategory::OVERTIME)->first();

            $isUserOvertimeEligible = $user->payrollInfo->overtime_setting->is(OvertimeSetting::ELIGIBLE);

            if ($isUserOvertimeEligible && $overtimePayrollComponent) {
                $amount = OvertimeService::calculate($user, $cutOffStartDate, $cutOffEndDate, $userBasicSalary);

                $this->createComponent($runPayrollUser, $overtimePayrollComponent, $amount);
            }
            // END

            /**
             * six, calculate task overtime
             */
            if (config('app.name') == 'SUNSHINE') {
                $taskOvertimePayrollComponent = PayrollComponent::tenanted()
                    ->whereCompany($runPayroll->company_id)
                    ->whenBranch($runPayroll->branch_id)
                    ->where('category', PayrollComponentCategory::TASK_OVERTIME)->first();

                if ($taskOvertimePayrollComponent) {
                    $amount = OvertimeService::calculateTaskOvertime($user, $cutOffStartDate, $cutOffEndDate);

                    $this->createComponent($runPayrollUser, $taskOvertimePayrollComponent, $amount);
                }
            }
            // END

            /**
             * seven, calculate BPJS FAMILY
             */
            if ($user->userBpjs && !$user->userBpjs->bpjs_kesehatan_family_no->is(\App\Enums\BpjsKesehatanFamilyNo::ZERO)) {
                // $bpjsKesehatanFamilyComponent = PayrollComponent::tenanted()
                //     ->whereCompany($runPayroll->company_id)
                //     ->whenBranch($runPayroll->branch_id)
                //     ->where('category', PayrollComponentCategory::BPJS_KESEHATAN_FAMILY)->first();

                if ($bpjsKesehatanFamilyComponent) {
                    $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                    if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                    // one percent from current_upahBpjsKesehatan, dikali bpjs_kesehatan_family_no
                    $amount = ($current_upahBpjsKesehatan * 0.01) * $user->userBpjs->bpjs_kesehatan_family_no->value;

                    $this->createComponent($runPayrollUser, $bpjsKesehatanFamilyComponent, $amount);
                }
            }
            // END

            // update total amount for each user
            $this->refreshRunPayrollUser($runPayrollUser);
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    public function refreshRunPayrollUser(RunPayrollUser|int $runPayrollUser)
    {
        if (!$runPayrollUser instanceof RunPayrollUser) {
            $runPayrollUser = RunPayrollUser::findOrFail($runPayrollUser);
        }

        $basicSalary = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('category', PayrollComponentCategory::BASIC_SALARY);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $allowanceTaxable = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_taxable', true);
        })->sum('amount');

        $allowanceNonTaxable = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_taxable', false);
        })->sum('amount');

        $additionalEarning = 0; // belum kepake

        $benefit = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::BENEFIT);
            $q->whereNotIn('category', [PayrollComponentCategory::COMPANY_JHT, PayrollComponentCategory::COMPANY_JP]);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $deduction = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::DEDUCTION);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $grossSalary = $basicSalary + $allowanceTaxable + $additionalEarning + $benefit;

        $tax = 0;
        $userPayrollInfo = $runPayrollUser->user->payrollInfo;
        if ($userPayrollInfo->tax_salary->is(TaxSalary::TAXABLE)) {
            $taxPercentage = ServicesRunPayrollService::calculateTax($runPayrollUser->user->payrollInfo->ptkp_status, $grossSalary);
            if ($userPayrollInfo->tax_method->is(TaxMethod::GROSS_UP)) {
                $grossUp1 = floatval(100 - $taxPercentage);
                $grossSalary2 = ($grossSalary / $grossUp1) * 100;

                $taxPercentage = ServicesRunPayrollService::calculateTax($runPayrollUser->user->payrollInfo->ptkp_status, $grossSalary2);

                $tax = $grossSalary2 * ($taxPercentage / 100);
            } else {
                $tax = $grossSalary * ($taxPercentage / 100);
            }
        }

        $runPayrollUser->update([
            'basic_salary' => $basicSalary,
            'gross_salary' => $grossSalary,
            'allowance' => $allowanceTaxable + $allowanceNonTaxable,
            'additional_earning' => $additionalEarning,
            'deduction' => $deduction,
            'benefit' => $benefit,
            'tax' => round($tax),
            'payroll_info' => $userPayrollInfo,
        ]);
    }

    public function getTotalWDNewUser(PayrollSetting $payrollSetting, User|int $user, $startDate, $endDate)
    {
        if (!$user instanceof User) {
            $user = User::find($user, ['id', 'type', 'group_id']);
        }

        $startDate = Carbon::createFromDate($startDate);
        $endDate = Carbon::createFromDate($endDate);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        // $companyHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereCompanyHoliday()->get();
        $nationalHolidays = Event::selectMinimalist()->whereCompany($user->company_id)->whereDateBetween($startDate, $endDate)->whereNationalHoliday()->get();

        // $isCountNationalHoliday = false;

        //if prorate_setting == Based ON Working Day
        if ($payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_WORKING_DAY)) {
            $isCountNationalHoliday = $payrollSetting->prorate_national_holiday_as_working_day ?? false;

            $totalWorkingDays = 0;
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'is_dayoff', 'name', 'clock_in', 'clock_out']);

                if (!$schedule || !$schedule->shift) {
                    continue;
                }

                $totalWorkingDays++;

                if (!$schedule->is_overide_national_holiday || $isCountNationalHoliday) {
                    $date = $date->format('Y-m-d');
                    $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                        return date('Y-m-d', strtotime($nh->start_at)) <= $date && date('Y-m-d', strtotime($nh->end_at)) >= $date;
                    });

                    if ($nationalHoliday) {
                        $totalWorkingDays--;
                        continue;
                    }
                }

                if (
                    $schedule->shift->is_dayoff
                    && (!isset($schedule->shift->is_request_shift) || $schedule->shift->is_request_shift == false)
                ) {
                    $totalWorkingDays--;
                    continue;
                }
            }
        } elseif ($payrollSetting->prorate_setting->is(ProrateSetting::BASE_ON_CALENDAR_DAY)) {
            //if prorate_setting == BASE_ON_CALENDAR_DAY
            $totalWorkingDays = $endDate->diffInDays($user->join_date);
            $totalWholeWorkingDays = count($dateRange);
        } elseif ($payrollSetting->prorate_setting->is(ProrateSetting::CUSTOM_ON_WORKING_DAY)) {
            //if prorate_setting == CUSTOM_ON_WORKING_DAY

        } elseif ($payrollSetting->prorate_setting->is(ProrateSetting::CUSTOM_ON_CALENDAR_DAY)) {
            //if prorate_setting == CUSTOM_ON_CALENDAR_DAY
        }

        return $totalWorkingDays;
    }

    // public static function getTotalAttend(User|int $user, Carbon | string $startDate, Carbon | string $endDate)
    public function getTotalWorkingDays(PayrollSetting $payrollSetting, User|int $user, Carbon | string $startDate, Carbon | string $endDate)
    {
        $userId = $user;
        if ($user instanceof User) {
            $userId = $user->id;
        }

        if (!($startDate instanceof Carbon)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
        }

        if (!($endDate instanceof Carbon)) {
            $endDate = Carbon::parse($endDate)->startOfDay();
        }

        $isFirstTimePayroll = $this->isFirstTimePayroll($user);
        if (!$isFirstTimePayroll) {
            $joinDate = Carbon::parse($user->join_date);
            if ($joinDate->between($startDate, $endDate)) {
                $startDate = $joinDate;
            }
        }

        $isCountNationalHoliday = false;
        if ($payrollSetting->prorate_setting->in([ProrateSetting::BASE_ON_WORKING_DAY, ProrateSetting::CUSTOM_ON_WORKING_DAY])) {
            $isCountNationalHoliday = $payrollSetting->prorate_national_holiday_as_working_day ?? false;
        }

        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $attendances = Attendance::where('user_id', $userId)
            ->where(
                fn($q) => $q->whereHas('details', fn($q) => $q->approved())->orHas('timeoff')
            )
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->with([
                'clockIn' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'clockOut' => fn($q) => $q->approved()->select('id', 'attendance_id'),
                'timeoff' => fn($q) => $q->select('id', 'is_cancelled'),
            ])
            ->get(['id', 'date', 'timeoff_id']);

        $totalAttend = 0;
        foreach ($dateRange as $date) {
            $todaySchedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'is_overide_national_holiday', 'is_overide_company_holiday', 'effective_date'], ['id', 'is_dayoff']);

            $attendanceOnDate = $attendances->firstWhere('date', $date->format('Y-m-d'));

            if ((!$todaySchedule?->shift || $todaySchedule?->shift->is_dayoff) && !$attendanceOnDate) {
                continue;
            }

            $nationalHoliday = Event::whereNationalHoliday()
                ->where('company_id', $user->company_id)
                ->where(
                    fn($q) => $q->whereDate('start_at', '<=', $date->format('Y-m-d'))
                        ->whereDate('end_at', '>=', $date->format('Y-m-d'))
                )
                ->exists();

            if (($nationalHoliday && $todaySchedule->is_overide_national_holiday == false) || $isCountNationalHoliday == false) {
                $totalAttend++;
                continue;
            }

            if ($attendanceOnDate?->timeoff && $attendanceOnDate->timeoff->approval_status == ApprovalStatus::APPROVED->value && $attendanceOnDate->timeoff->is_cancelled == false) {
                $totalAttend++;
                continue;
            }

            if ($attendanceOnDate?->clockIn && $attendanceOnDate?->clockOut) {
                $totalAttend++;
                continue;
            }
        }

        return $totalAttend;
    }
}
