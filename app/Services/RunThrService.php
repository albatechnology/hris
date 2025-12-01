<?php

namespace App\Services;

use App\Enums\CountrySettingKey;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Enums\PtkpStatus;
use App\Enums\RunPayrollStatus;
use App\Enums\TaxMethod;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\RunThr;
use App\Models\RunThrUser;
use App\Models\RunThrUserComponent;
use App\Models\UpdatePayrollComponentDetail;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RunThrService
{
    /**
     * execute run payroll
     *
     * @param  array $request
     */
    public static function execute(array $request): RunThr | Exception | JsonResponse
    {
        $payrollSetting = PayrollSetting::with('company')->whereCompany($request['company_id'])->first();
        // if (!$payrollSetting->cut_off_attendance_start_date || !$payrollSetting->cut_off_attendance_end_date) {
        //     return response()->json([
        //         'success' => false,
        //         'data' => 'Please set your Payroll cut off date before submit Run Payroll',
        //     ]);
        // }
        // $cutOffStartDate = Carbon::parse($payrollSetting->cut_off_attendance_start_date . '-' . $request['period']);
        // $cutOffEndDate = $cutOffStartDate->clone()->lastOfMonth();
        // $request = array_merge($request, [
        //     'cut_off_start_date' => $cutOffStartDate->toDateString(),
        //     'cut_off_end_date' => $cutOffEndDate->toDateString(),
        // ]);

        DB::beginTransaction();
        try {
            $runThr = self::createRunThr($request);

            $runThrDetail = self::createDetails($payrollSetting, $runThr, $request);

            // check if there's json error response
            if (!$runThrDetail->getData()?->success) {
                DB::rollBack();
                return response()->json($runThrDetail->getData());
            }

            DB::commit();

            return $runThr;
        } catch (Exception $e) {
            DB::rollBack();

            throw new Exception($e);
        }
    }

    /**
     * create run payroll
     *
     * @param  Request  $request
     */
    public static function createRunThr(array $request): RunThr
    {
        return RunThr::create([
            'user_id' => auth('sanctum')->id(),
            'company_id' => $request['company_id'],
            'thr_date' => $request['thr_date'],
            'payment_date' => $request['payment_date'],
            'status' => RunPayrollStatus::REVIEW,
        ]);
    }

    public static function calculateProrateTotalDays(int $totalWorkingDays, Carbon $startDate, Carbon $endDate, bool $isSubOneDay = false): int
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

    public static function prorate(int|float $basicAmount, int|float $updatePayrollComponentAmount, int $totalWorkingDays, Carbon $cutOffStartDate, Carbon $cutOffEndDate, Carbon $startEffectiveDate, Carbon|null $endEffectiveDate, bool $isDebug = false): int|float
    {
        // effective_date is between period
        if ($startEffectiveDate->between($cutOffStartDate, $cutOffEndDate)) {
            // jika terdapat end_date
            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($cutOffEndDate)) {
                $totalDaysFromCutOffStartDateToStartEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $cutOffStartDate, $startEffectiveDate, true);
                $startSalary = ($totalDaysFromCutOffStartDateToStartEffectiveDate / $totalWorkingDays) * $basicAmount;

                $totalDaysFromStartEffectiveDateToEndEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $startEffectiveDate, $endEffectiveDate);
                $middleSalary = ($totalDaysFromStartEffectiveDateToEndEffectiveDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $endSalary = 0;
                if ($endEffectiveDate->lessThan($cutOffEndDate)) {
                    $totalDaysFromEndEffectiveDateToCutOffEndDate = self::calculateProrateTotalDays($totalWorkingDays, $endEffectiveDate, $cutOffEndDate, true);
                    $endSalary = ($totalDaysFromEndEffectiveDateToCutOffEndDate / $totalWorkingDays) * $basicAmount;
                }

                $basicAmount = $startSalary + $middleSalary + $endSalary;
            } else {
                // NORMAL CALCULATION
                $totalDaysFromCutOffStartDateToStartEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $cutOffStartDate, $startEffectiveDate, true);
                $startSalary = ($totalDaysFromCutOffStartDateToStartEffectiveDate / $totalWorkingDays) * $basicAmount;
                $totalDaysFromStartEffectiveDateToCutOffEndDate = self::calculateProrateTotalDays($totalWorkingDays, $startEffectiveDate, $cutOffEndDate);
                $endSalary = ($totalDaysFromStartEffectiveDateToCutOffEndDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $basicAmount = $startSalary + $endSalary;
            }
        } else {
            if ($endEffectiveDate && $endEffectiveDate->lessThanOrEqualTo($cutOffEndDate)) {
                $totalDaysFromCutOffStartDateToEndEffectiveDate = self::calculateProrateTotalDays($totalWorkingDays, $cutOffStartDate, $endEffectiveDate);
                $startSalary = ($totalDaysFromCutOffStartDateToEndEffectiveDate / $totalWorkingDays) * $updatePayrollComponentAmount;

                $totalDaysFromEndEffectiveDateToCutOffEndDate = self::calculateProrateTotalDays($totalWorkingDays, $endEffectiveDate, $cutOffEndDate, true);
                $endSalary = ($totalDaysFromEndEffectiveDateToCutOffEndDate / $totalWorkingDays) * $basicAmount;

                $basicAmount = $startSalary + $endSalary;
            } else {
                $basicAmount = $updatePayrollComponentAmount;
            }
        }
        return $basicAmount;
    }

    /**
     * create run payroll details
     *
     * @param  PayrollSetting   $payrollSetting
     * @param  RunThr   $runThr
     * @param  Request      $request
     * @return JsonResponse
     */
    public static function createDetails(PayrollSetting $payrollSetting, RunThr $runThr, array $request): JsonResponse
    {
        $cutOffStartDate = Carbon::parse($runThr->cut_off_start_date);
        $cutOffEndDate = Carbon::parse($runThr->cut_off_end_date);
        // $cutoffDiffDay = $cutOffStartDate->diff($cutOffEndDate)->days;
        $company = $payrollSetting->company;

        $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
        $max_jp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::JP_MAXIMUM_SALARY)?->value;

        // calculate for each user
        $userJoinDateBefore = date('Y-m-d', strtotime($request['thr_date'] . ' -30 days'));
        foreach (explode(',', $request['user_ids']) as $userId) {
            /** @var \App\Models\User $user */
            $user = User::whereDate('join_date', '<=', $userJoinDateBefore)->find($userId);
            if (!$user) continue;
            if ($user->resign_date) {
                $resignDate = Carbon::parse($user->resign_date);
                if ($resignDate->lessThan($cutOffStartDate)) continue;
            }

            $runThrUser = self::assignUser($runThr, $userId);
            $userBasicSalary = $user->payrollInfo?->basic_salary;
            $totalWorkingDays = AttendanceService::getTotalWorkingDays($user, $cutOffStartDate, $cutOffEndDate);

            $updatePayrollComponentDetails = UpdatePayrollComponentDetail::with('updatePayrollComponent')
                ->where('user_id', $userId)
                ->whereHas('updatePayrollComponent', function ($q) use ($request) {
                    $q->whereCompany($request['company_id']);
                    $q->where(function ($q2) {
                        $q2->whereNull('end_date');
                        $q2->orWhere('end_date', '>', now());
                    });
                    $q->where('effective_date', '<=', now());
                })->orderByDesc('id')->get();

            /**
             * first, calculate basic salary. for now basic salary component is required
             */
            $basicSalaryComponent = PayrollComponent::tenanted()->where('company_id', $runThr->company_id)->where('category', PayrollComponentCategory::BASIC_SALARY)->firstOrFail();
            $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $basicSalaryComponent->id)->first();

            if ($updatePayrollComponentDetail) {
                // $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

                // end_date / endEffectiveDate can be null
                // $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

                // calculate prorate
                // $userBasicSalary = self::prorate($userBasicSalary, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $cutOffStartDate, $cutOffEndDate, $startEffectiveDate, $endEffectiveDate);
                $userBasicSalary = $updatePayrollComponentDetail->new_amount;
            }

            $amount = self::calculatePayrollComponentPeriodType($basicSalaryComponent, $userBasicSalary, $totalWorkingDays, $runThrUser);

            // // prorate basic salary
            // $joinDate = Carbon::parse($user->join_date)->startOfDay();
            // $totalWorkingMonths = $joinDate->diffInDays($request['thr_date']);
            // $totalWorkingMonths = intdiv($totalWorkingMonths, 30);
            // $thrMultiplier = $totalWorkingMonths >= 12 ? 1 : (($totalWorkingMonths + 1) / 12);

            // $amount = $thrMultiplier * $amount;

            self::createComponent($runThrUser, $basicSalaryComponent, $amount);
            // END

            /**
             * second, calculate payroll component where not default
             */
            // $payrollComponents = PayrollComponent::tenanted()->where('company_id', $runThr->company_id)->whereNotDefault()->get();
            // dd($payrollComponents);
            // $payrollComponents->each(function ($payrollComponent) use ($user, $updatePayrollComponentDetails, $runThrUser,  $totalWorkingDays, $cutOffStartDate, $cutOffEndDate) {

            //     if ($payrollComponent->amount == 0 && count($payrollComponent->formulas)) {
            //         $amount = FormulaService::calculate(user: $user, model: $payrollComponent, formulas: $payrollComponent->formulas, startPeriod: $cutOffStartDate, endPeriod: $cutOffEndDate);
            //     } else {
            //         $amount = $payrollComponent->amount;
            //     }

            //     $updatePayrollComponentDetail = $updatePayrollComponentDetails->where('payroll_component_id', $payrollComponent->id)->first();
            //     if ($updatePayrollComponentDetail) {
            //         $startEffectiveDate = Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->effective_date);

            //         // end_date / endEffectiveDate can be null
            //         $endEffectiveDate = $updatePayrollComponentDetail->updatePayrollComponent->end_date ? Carbon::parse($updatePayrollComponentDetail->updatePayrollComponent->end_date) : null;

            //         // calculate prorate
            //         $amount = self::prorate($amount, $updatePayrollComponentDetail->new_amount, $totalWorkingDays, $cutOffStartDate, $cutOffEndDate, $startEffectiveDate, $endEffectiveDate, true);
            //     }

            //     $amount = self::calculatePayrollComponentPeriodType($payrollComponent, $amount, $totalWorkingDays, $runThrUser);

            //     self::createComponent($runThrUser, $payrollComponent, $amount);
            // });
            // END

            /**
             * third, calculate alpa
             */
            // if ($user->payrollInfo?->is_ignore_alpa == false) {
            //     $alpaComponent = PayrollComponent::tenanted()->where('company_id', $runThr->company_id)->where('category', PayrollComponentCategory::ALPA)->first();
            //     if ($alpaComponent) {
            //         $alpaUpdateComponent = $updatePayrollComponentDetails->where('payroll_component_id', $alpaComponent->id)->first();
            //         if ($alpaUpdateComponent) {
            //             $amount = $alpaUpdateComponent->new_amount;
            //         } else {
            //             // get total alpa di range tgl cuttoff
            //             // potongan = (totalAlpa/totalHariKerja)*(basicSalary+SUM(allowance))
            //             $totalWorkingDays = ScheduleService::getTotalWorkingDaysInPeriod($user, $cutOffStartDate, $cutOffEndDate);
            //             $totalAlpa = AttendanceService::getTotalAlpa($user, $cutOffStartDate, $cutOffEndDate);

            //             $totalAllowance = $runThrUser->components()->whereHas('payrollComponent', fn($q) => $q->where('type', PayrollComponentType::ALLOWANCE)->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY, PayrollComponentCategory::OVERTIME]))->sum('amount');
            //             $amount = round(max(($totalAlpa / $totalWorkingDays) * ($userBasicSalary + $totalAllowance), 0));

            //             $amount = self::calculatePayrollComponentPeriodType($alpaComponent, $amount, $totalWorkingDays, $runThrUser);
            //         }

            //         $amount = self::calculatePayrollComponentPeriodType($alpaComponent, $amount, $totalWorkingDays, $runThrUser);
            //         self::createComponent($runThrUser, $alpaComponent, $amount);
            //     }
            // }
            // END

            /**
             * fourth, calculate bpjs
             */
            if ($company->countryTable?->id == 1 && $user->userBpjs) {
                $bpjsPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->whereBpjs()->get();

                // calculate bpjs
                // init bpjs variable
                $current_upahBpjsKesehatan = $user->userBpjs->upah_bpjs_kesehatan;
                if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;

                $current_upahBpjsKetenagakerjaan = $user->userBpjs->upah_bpjs_ketenagakerjaan;
                if ($current_upahBpjsKetenagakerjaan > $max_jp) $current_upahBpjsKetenagakerjaan = $max_jp;

                // bpjs kesehatan
                $company_percentageBpjsKesehatan = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE)?->value;

                $employee_percentageBpjsKesehatan = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE)?->value;

                $company_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($company_percentageBpjsKesehatan / 100);

                $employee_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($employee_percentageBpjsKesehatan / 100);

                // jkk
                $company_percentageJkk = $company->jkk_tier->getValue() ?? 0;
                $company_totalJkk = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkk / 100);

                // jkm
                $company_percentageJkm = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JKM_PERCENTAGE)?->value;
                $company_totalJkm = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJkm / 100);

                // jht
                $company_percentageJht = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JHT_PERCENTAGE)?->value;
                $company_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($company_percentageJht / 100);

                $employee_percentageJht = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE)?->value;
                $employee_totalJht = $user->userBpjs->upah_bpjs_ketenagakerjaan * ($employee_percentageJht / 100);

                // jp
                $company_percentageJp = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JP_PERCENTAGE)?->value;

                $employee_percentageJp = (float)$company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JP_PERCENTAGE)?->value;

                $company_totalJp = $current_upahBpjsKetenagakerjaan * ($company_percentageJp / 100);

                $employee_totalJp = $current_upahBpjsKetenagakerjaan * ($employee_percentageJp / 100);

                foreach ($bpjsPayrollComponents as $bpjsPayrollComponent) {
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_BPJS_KESEHATAN)) $amount = $company_totalBpjsKesehatan;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN)) $amount = $employee_totalBpjsKesehatan;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKK)) $amount = $company_totalJkk;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKM)) $amount = $company_totalJkm;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JHT)) $amount = $company_totalJht;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JHT)) $amount = $employee_totalJht;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JP)) $amount = $company_totalJp;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JP)) $amount = $employee_totalJp;

                    $amount = self::calculatePayrollComponentPeriodType($bpjsPayrollComponent, $amount, $totalWorkingDays, $runThrUser);

                    self::createComponent($runThrUser, $bpjsPayrollComponent, $amount);
                }
            }
            // END

            /**
             * five, calculate overtime
             */
            // $overtimePayrollComponent = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('category', PayrollComponentCategory::OVERTIME)->first();

            // $isUserOvertimeEligible = $user->payrollInfo->overtime_setting->is(OvertimeSetting::ELIGIBLE);

            // if ($isUserOvertimeEligible && $overtimePayrollComponent) {
            //     $amount = OvertimeService::calculate($user, $cutOffStartDate, $cutOffEndDate, $userBasicSalary);

            //     self::createComponent($runThrUser, $overtimePayrollComponent, $amount);
            // }
            // END

            /**
             * six, calculate task overtime
             */
            // $taskOvertimePayrollComponent = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('category', PayrollComponentCategory::TASK_OVERTIME)->first();
            // if ($taskOvertimePayrollComponent) {
            //     $amount = OvertimeService::calculateTaskOvertime($user, $cutOffStartDate, $cutOffEndDate);

            //     self::createComponent($runThrUser, $taskOvertimePayrollComponent, $amount);
            // }
            // END

            // update total amount for each user
            self::refreshRunThrUser($runThrUser);
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * create run payroll details
     *
     * @param  RunThr   $runThr
     * @param  string|int   $userId
     * @return RunThrUser
     */
    public static function assignUser(RunThr $runThr, string|int $userId): RunThrUser
    {
        return $runThr->users()->create(['user_id' => $userId]);
    }

    /**
     * create run payroll user components
     *
     * @param  RunThrUser   $runThrUser
     * @param  int              $payrollComponentId
     * @param  int|float        $amomunt
     * @param  bool             $isEditable
     * @return RunThrUserComponent
     */
    public static function createComponent(RunThrUser $runThrUser, PayrollComponent $payrollComponent, int|float $amount = 0, ?bool $isEditable = true): RunThrUserComponent
    {
        return $runThrUser->components()->create([
            'payroll_component_id' => $payrollComponent->id,
            'amount' => $amount,
            'is_editable' => $isEditable,
            'payroll_component' => $payrollComponent,
        ]);
    }

    /**
     * Calculates the amount of a payroll component based on its period type.
     *
     * @param PayrollComponent $payrollComponent The payroll component to calculate.
     * @param int|float $amount The initial amount of the component. Default is 0.
     * @param int $cutoffDiffDay The number of days between the cutoff start and end dates. Default is 0.
     * @param RunThrUser|null $runThrUser The run payroll user associated with the component. Default is null.
     * @return int|float The calculated amount of the component.
     */
    public static function calculatePayrollComponentPeriodType(PayrollComponent $payrollComponent, int|float $amount = 0, int $cutoffDiffDay = 0, ?RunThrUser $runThrUser = null): int|float
    {
        if ($payrollComponent->category->is(PayrollComponentCategory::ALPA)) {
            return $amount;
        }

        switch ($payrollComponent->period_type) {
            case PayrollComponentPeriodType::DAILY:
                // rate_amount * cutoff diff days
                if (!$payrollComponent->formulas) $amount = $amount * $cutoffDiffDay;

                break;
            case PayrollComponentPeriodType::MONTHLY:
                $amount = $amount;

                break;
            case PayrollComponentPeriodType::ONE_TIME:
                if ($runThrUser->user->oneTimePayrollComponents()->firstWhere('payroll_component_id', $payrollComponent->id)) {
                    $amount = 0;
                } else {
                    $runThrUser->user->oneTimePayrollComponents()->create(['payroll_component_id' => $payrollComponent->id]);
                    $amount = $amount;
                }

                break;
            default:
                //

                break;
        }

        return $amount;
    }

    public static function refreshRunThrUser(RunThrUser|int $runThrUser)
    {
        if (!$runThrUser instanceof RunThrUser) {
            $runThrUser = RunThrUser::findOrFail($runThrUser);
        }

        $basicSalary = $runThrUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('category', PayrollComponentCategory::BASIC_SALARY);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $allowanceTaxable = $runThrUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_taxable', true);
        })->sum('amount');

        $allowanceNonTaxable = $runThrUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_taxable', false);
        })->sum('amount');

        $additionalEarning = 0; // belum kepake

        $benefit = $runThrUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::BENEFIT);
            $q->whereNotIn('category', [PayrollComponentCategory::COMPANY_JHT, PayrollComponentCategory::COMPANY_JP]);
            // $q->where('is_calculateable', true);
        })->sum('amount');

        $deduction = $runThrUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::DEDUCTION);
            // $q->where('is_calculateable', true);
        })->sum('amount');


        $grossSalary = $basicSalary + $allowanceTaxable + $additionalEarning + $benefit;

        $userPayrollInfo = $runThrUser->user->payrollInfo;

        $taxPercentage = self::calculateTax($runThrUser->user->payrollInfo->ptkp_status, $grossSalary);

        if ($userPayrollInfo->tax_method->is(TaxMethod::GROSS_UP)) {
            $grossUp1 = floatval(100 - $taxPercentage);
            $grossSalary2 = ($grossSalary / $grossUp1) * 100;

            $taxPercentage = self::calculateTax($runThrUser->user->payrollInfo->ptkp_status, $grossSalary2);

            $tax = $grossSalary2 * ($taxPercentage / 100);
        } else {
            $tax = $grossSalary * ($taxPercentage / 100);
        }

        //NEW THR Prorate disimpan ke dalam kolom baru di database
        $benefitForTotalMonth = $runThrUser->components()
            ->whereHas('payrollComponent', fn($q) => $q->whereIn('category', [
                PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
                PayrollComponentCategory::COMPANY_JKK,
                PayrollComponentCategory::COMPANY_JKM,
            ]))
            ->sum('amount');

        $totalEarning = round($basicSalary + ($allowanceTaxable + $allowanceNonTaxable) + $additionalEarning);
        $totalMonth = round($totalEarning + $benefitForTotalMonth);

        //Hitung Prorate
        $joinDate = Carbon::parse($runThrUser->user->join_date)->startOfDay();
        $thrDate = Carbon::parse($runThrUser->runThr->thr_date)->startOfDay();
        $months = $joinDate->diffInMonths($thrDate,true,false);
        // $months = intdiv($days, 12);
        // dd($months);
        // $thrMultiplier = $months >= 12 ? 1 : (($months + 1) / 12);
        $thrMultiplier = $months >= 12 ? 1 : ($months / 12);
        // dd($thrMultiplier, $months);
        $thrProrate = round($thrMultiplier * $basicSalary);
        // dump($thrProrate);

        $totalBebanMonth = round($totalMonth + $thrProrate);
        // dump($totalBebanMonth);
        $taxAfter = $totalBebanMonth * (self::calculateTax($runThrUser->user->payrollInfo->ptkp_status, $totalBebanMonth) / 100);
        // dump($taxAfter);
        $taxThr = round($taxAfter - $tax);
        // dump($taxThr);
        $thpThr = round($thrProrate - $taxThr);
        // dump($thpThr);
        $basicSalaryPersisted = $thrProrate;
        // dump($basicSalaryPersisted);

        $runThrUser->update([
            'basic_salary' => $basicSalaryPersisted,
            'gross_salary' => $grossSalary,
            'allowance' => $allowanceTaxable + $allowanceNonTaxable,
            'additional_earning' => $additionalEarning,
            'deduction' => $deduction,
            'benefit' => $benefit,
            'tax' => round($tax),
            'payroll_info' => $userPayrollInfo,
        ]);
    }

    public static function calculateTax(PtkpStatus $ptkpStatus, float $grossSalary): float
    {
        $taxPercentage = 0;

        if (in_array($ptkpStatus, [PtkpStatus::TK_0, PtkpStatus::TK_1, PtkpStatus::K_0])) {
            if ($grossSalary <= 5400000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 5400000 && $grossSalary <= 5650000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 5650000 && $grossSalary <= 5950000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 5950000 && $grossSalary <= 6300000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 6300000 && $grossSalary <= 6750000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 6750000 && $grossSalary <= 7500000) {
                $taxPercentage = 1.25;
            } else if ($grossSalary > 7500000 && $grossSalary <= 8550000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 8550000 && $grossSalary <= 9650000) {
                $taxPercentage = 1.75;
            } else if ($grossSalary > 9650000 && $grossSalary <= 10050000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 10050000 && $grossSalary <= 10350000) {
                $taxPercentage = 2.25;
            } else if ($grossSalary > 10350000 && $grossSalary <= 10700000) {
                $taxPercentage = 2.5;
            } else if ($grossSalary > 10700000 && $grossSalary <= 11050000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 11050000 && $grossSalary <= 11600000) {
                $taxPercentage = 3.5;
            } else if ($grossSalary > 11600000 && $grossSalary <= 12500000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 12500000 && $grossSalary <= 13750000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 13750000 && $grossSalary <= 15100000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 15100000 && $grossSalary <= 16950000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 16950000 && $grossSalary <= 19750000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 19750000 && $grossSalary <= 24150000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 24150000 && $grossSalary <= 26450000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 26450000 && $grossSalary <= 28000000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 28000000 && $grossSalary <= 30050000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 30050000 && $grossSalary <= 32400000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 32400000 && $grossSalary <= 35400000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 35400000 && $grossSalary <= 39100000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 39100000 && $grossSalary <= 43850000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 43850000 && $grossSalary <= 47800000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 47800000 && $grossSalary <= 51400000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 51400000 && $grossSalary <= 56300000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 56300000 && $grossSalary <= 62200000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 62200000 && $grossSalary <= 68600000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 68600000 && $grossSalary <= 77500000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 77500000 && $grossSalary <= 89000000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 89000000 && $grossSalary <= 103000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 103000000 && $grossSalary <= 125000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 125000000 && $grossSalary <= 157000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 157000000 && $grossSalary <= 206000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 206000000 && $grossSalary <= 337000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 337000000 && $grossSalary <= 454000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 454000000 && $grossSalary <= 550000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 550000000 && $grossSalary <= 695000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 695000000 && $grossSalary <= 910000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 910000000 && $grossSalary <= 1400000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1400000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        if (in_array($ptkpStatus, [PtkpStatus::TK_2, PtkpStatus::TK_3, PtkpStatus::K_1, PtkpStatus::K_2])) {
            if ($grossSalary <= 6200000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 6200000 && $grossSalary <= 6500000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 6500000 && $grossSalary <= 6850000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 6850000 && $grossSalary <= 7300000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 7300000 && $grossSalary <= 9200000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 9200000 && $grossSalary <= 10750000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 10750000 && $grossSalary <= 11250000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 11250000 && $grossSalary <= 11600000) {
                $taxPercentage = 2.5;
            } else if ($grossSalary > 11600000 && $grossSalary <= 12600000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 12600000 && $grossSalary <= 13600000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 13600000 && $grossSalary <= 14950000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 14950000 && $grossSalary <= 16400000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 16400000 && $grossSalary <= 18450000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 18450000 && $grossSalary <= 21850000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 21850000 && $grossSalary <= 26000000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 26000000 && $grossSalary <= 27700000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 27700000 && $grossSalary <= 29350000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 29350000 && $grossSalary <= 31450000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 31450000 && $grossSalary <= 33950000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 33950000 && $grossSalary <= 37100000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 37100000 && $grossSalary <= 41100000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 41100000 && $grossSalary <= 45800000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 45800000 && $grossSalary <= 49500000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 49500000 && $grossSalary <= 53800000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 53800000 && $grossSalary <= 58500000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 58500000 && $grossSalary <= 64000000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 64000000 && $grossSalary <= 71000000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 71000000 && $grossSalary <= 80000000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 80000000 && $grossSalary <= 93000000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 93000000 && $grossSalary <= 109000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 109000000 && $grossSalary <= 129000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 129000000 && $grossSalary <= 163000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 163000000 && $grossSalary <= 211000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 211000000 && $grossSalary <= 374000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 374000000 && $grossSalary <= 459000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 459000000 && $grossSalary <= 555000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 555000000 && $grossSalary <= 704000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 704000000 && $grossSalary <= 957000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 957000000 && $grossSalary <= 1405000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1405000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        if (in_array($ptkpStatus, [PtkpStatus::K_3])) {
            if ($grossSalary <= 6600000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 6600000 && $grossSalary <= 6950000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 6950000 && $grossSalary <= 7350000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 7350000 && $grossSalary <= 7800000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 7800000 && $grossSalary <= 8850000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 8850000 && $grossSalary <= 9800000) {
                $taxPercentage = 1.25;
            } else if ($grossSalary > 9800000 && $grossSalary <= 10950000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 10950000 && $grossSalary <= 11200000) {
                $taxPercentage = 1.75;
            } else if ($grossSalary > 11200000 && $grossSalary <= 12050000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 12050000 && $grossSalary <= 12950000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 12950000 && $grossSalary <= 14150000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 14150000 && $grossSalary <= 15550000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 15550000 && $grossSalary <= 17050000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 17050000 && $grossSalary <= 19500000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 19500000 && $grossSalary <= 22700000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 22700000 && $grossSalary <= 26600000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 26600000 && $grossSalary <= 28100000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 28100000 && $grossSalary <= 30100000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 30100000 && $grossSalary <= 32600000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 32600000 && $grossSalary <= 35400000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 35400000 && $grossSalary <= 38900000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 38900000 && $grossSalary <= 43000000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 43000000 && $grossSalary <= 47400000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 47400000 && $grossSalary <= 51200000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 51200000 && $grossSalary <= 55800000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 55800000 && $grossSalary <= 60400000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 60400000 && $grossSalary <= 66700000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 66700000 && $grossSalary <= 74500000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 74500000 && $grossSalary <= 83200000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 83200000 && $grossSalary <= 95600000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 95600000 && $grossSalary <= 110000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 110000000 && $grossSalary <= 134000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 134000000 && $grossSalary <= 169000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 169000000 && $grossSalary <= 221000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 221000000 && $grossSalary <= 390000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 390000000 && $grossSalary <= 463000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 463000000 && $grossSalary <= 561000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 561000000 && $grossSalary <= 709000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 709000000 && $grossSalary <= 965000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 965000000 && $grossSalary <= 1419000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1419000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        return $taxPercentage;
    }
}
// // calculate bpjs
// if ($runThrUser->user->userBpjs) {
//     // init bpjs variable
//     $current_upahBpjsKesehatan = $runThrUser->user->userBpjs->upah_bpjs_kesehatan;
//     $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
//     if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;


//     $current_upahBpjsKetenagakerjaan = $runThrUser->user->userBpjs->upah_bpjs_ketenagakerjaan;
//     $max_jp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::JP_MAXIMUM_SALARY)?->value;
//     if ($current_upahBpjsKetenagakerjaan > $max_jp) $current_upahBpjsKetenagakerjaan = $max_jp;

//     // bpjs kesehatan
//     $company_percentageBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE)?->value;
//     $employee_percentageBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE)?->value;

//     $company_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($company_percentageBpjsKesehatan / 100);
//     $employee_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($employee_percentageBpjsKesehatan / 100);

//     // jkk
//     $company_percentageJkk = $company->npp?->jkk ?? 0;
//     $company_totalJkk = $current_upahBpjsKetenagakerjaan * ($company_percentageJkk / 100);

//     // jkm
//     $company_percentageJkm = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JKM_PERCENTAGE)?->value;
//     $company_totalJkm = $current_upahBpjsKetenagakerjaan * ($company_percentageJkm / 100);

//     // jht
//     $company_percentageJht = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JHT_PERCENTAGE)?->value;
//     $employee_percentageJht = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE)?->value;
//     $company_totalJht = $current_upahBpjsKetenagakerjaan * ($company_percentageJht / 100);
//     $employee_totalJht = $current_upahBpjsKetenagakerjaan * ($employee_percentageJht / 100);

//     // jp
//     $company_percentageJp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JP_PERCENTAGE)?->value;
//     $employee_percentageJp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JP_PERCENTAGE)?->value;

//     $company_totalJp = $current_upahBpjsKetenagakerjaan * ($company_percentageJp / 100);
//     $employee_totalJp = $current_upahBpjsKetenagakerjaan * ($employee_percentageJp / 100);

//     // company = benefit (tidak perlu kalkulasi, hanya catat)
//     // employee = deduction (kalkulasi)
//         'company_totalBpjsKesehatan' => $company_totalBpjsKesehatan,
//         'employee_totalBpjsKesehatan' => $employee_totalBpjsKesehatan,
//         'company_totalJkk' => $company_totalJkk,
//         'company_totalJkm' => $company_totalJkm,
//         'company_totalJht' => $company_totalJht,
//         'employee_totalJht' => $employee_totalJht,
//         'company_totalJp' => $company_totalJp,
//         'employee_totalJp' => $employee_totalJp,
//     ]);
// }
// // end calculate bpjs
