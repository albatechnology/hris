<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\CountrySettingKey;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Enums\PtkpStatus;
use App\Enums\RateType;
use App\Enums\RunPayrollStatus;
use App\Models\Company;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\UpdatePayrollComponent;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RunPayrollService
{
    /**
     * execute run payroll
     *
     * @param  array $request
     */
    public static function execute(array $request): RunPayroll | Exception | JsonResponse
    {
        DB::beginTransaction();
        try {
            $runPayroll = self::createRunPayroll($request);

            $runPayrollDetail = self::createDetails($runPayroll, $request);

            // check if there's json error response
            if (!$runPayrollDetail->getData()?->success) {
                DB::rollBack();
                return response()->json($runPayrollDetail->getData());
            }

            DB::commit();

            return $runPayroll;
        } catch (\Exception $th) {
            DB::rollBack();

            throw new Exception($th);
        }
    }

    /**
     * create run payroll
     *
     * @param  Request  $request
     */
    public static function createRunPayroll(array $request): RunPayroll
    {
        return auth('sanctum')->user()->runPayrolls()->create([
            'company_id' => $request['company_id'],
            'period' => $request['period'],
            'payment_schedule' => $request['payment_schedule'],
            'status' => RunPayrollStatus::REVIEW,
        ]);
    }

    /**
     * create run payroll details
     *
     * @param  RunPayroll   $runPayroll
     * @param  Request      $request
     * @return JsonResponse
     */
    public static function createDetails(RunPayroll $runPayroll, array $request): JsonResponse
    {
        $payrollSetting = PayrollSetting::whereCompany($request['company_id'])->first();
        if (!$payrollSetting->cutoff_attendance_start_date || !$payrollSetting->cutoff_attendance_end_date) {
            return response()->json([
                'success' => false,
                'data' => 'Please set your Payroll Setting before submit Run Payroll',
            ]);
        }

        // get cut off date
        $cutoffAttendanceStartDate = Carbon::parse($payrollSetting->cutoff_attendance_start_date . '-' . $request['period']);
        $cutoffAttendanceEndDate = Carbon::parse($payrollSetting->cutoff_attendance_start_date . '-' . $request['period'])->addMonth(1);
        $cutoffDiffDay = $cutoffAttendanceStartDate->diff($cutoffAttendanceEndDate)->days - 1;

        // calculate for each user
        foreach (explode(',', $request['user_ids']) as $userId) {
            $runPayrollUser = self::assignUser($runPayroll, $userId);
            $company = Company::find($request['company_id']);

            // updated payroll component
            $updatePayrollComponent = UpdatePayrollComponent::tenanted()->where(function ($q) use ($request, $userId) {
                $q->whereCompany($request['company_id']);
                $q->where(function ($q2) {
                    $q2->whereNull('end_date');
                    $q2->orWhere('end_date', '>', now());
                });
                $q->where('effective_date', '<=', now());
                $q->whereHas('details', function ($q2) use ($userId) {
                    $q2->where('user_id', $userId);
                });
            })->first();

            // define user basic salary & bpjs
            $userBasicSalary = $runPayrollUser->user->payrollInfo?->basic_salary;

            // default payroll component
            $defaultPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('is_default', true)->whereNotIn('category', [
                PayrollComponentCategory::OVERTIME,
                PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
                PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN,
                PayrollComponentCategory::COMPANY_JKK,
                PayrollComponentCategory::COMPANY_JKM,
                PayrollComponentCategory::COMPANY_JHT,
                PayrollComponentCategory::EMPLOYEE_JHT,
                PayrollComponentCategory::COMPANY_JP,
                PayrollComponentCategory::EMPLOYEE_JP,
            ])->get();
            foreach ($defaultPayrollComponents as $defaultPayrollComponent) {
                // check if payroll component is updated on UpdatePayrollComponent::class
                $updatePayrollComponentDetail = $updatePayrollComponent?->details()->where('payroll_component_id', $defaultPayrollComponent->id)->first();
                if ($updatePayrollComponentDetail) {
                    $amount = $updatePayrollComponentDetail->new_amount;

                    // override $userBasicSalary if there's an updated data on UpdatePayrollComponent::class
                    if ($updatePayrollComponentDetail->payrollComponent->category->is(PayrollComponentCategory::BASIC_SALARY)) $userBasicSalary = $amount;
                } else {
                    // if the default amount is empty || 0
                    if ($defaultPayrollComponent->amount == 0 && count($defaultPayrollComponent->formulas)) {
                        $amount = FormulaService::calculate(user: $runPayrollUser->user, model: $defaultPayrollComponent, formulas: $defaultPayrollComponent->formulas, startPeriod: $cutoffAttendanceStartDate, endPeriod: $cutoffAttendanceEndDate);
                    } else if ($defaultPayrollComponent->category->is(PayrollComponentCategory::BASIC_SALARY)) {
                        $amount = $userBasicSalary;
                    } else {
                        $amount = $defaultPayrollComponent->amount;
                    }
                }

                $amount = self::calculatePayrollComponentPeriodType($defaultPayrollComponent, $amount, $cutoffDiffDay, $runPayrollUser);

                self::createComponent($runPayrollUser, $defaultPayrollComponent->id, $amount);
            }

            // bpjs payroll component
            if ($company->countryTable?->id == 1 && $runPayrollUser->user->userBpjs) {
                $bpjsPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('is_default', true)->whereIn('category', [
                    PayrollComponentCategory::BPJS_KESEHATAN,
                    PayrollComponentCategory::BPJS_KETENAGAKERJAAN,
                    PayrollComponentCategory::COMPANY_BPJS_KESEHATAN,
                    PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN,
                    PayrollComponentCategory::COMPANY_JKK,
                    PayrollComponentCategory::COMPANY_JKM,
                    PayrollComponentCategory::COMPANY_JHT,
                    PayrollComponentCategory::EMPLOYEE_JHT,
                    PayrollComponentCategory::COMPANY_JP,
                    PayrollComponentCategory::EMPLOYEE_JP,
                ])->get();

                // calculate bpjs
                // init bpjs variable
                $current_upahBpjsKesehatan = $runPayrollUser->user->userBpjs->upah_bpjs_kesehatan;
                $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
                if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;


                $current_upahBpjsKetenagakerjaan = $runPayrollUser->user->userBpjs->upah_bpjs_ketenagakerjaan;
                $max_jp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::JP_MAXIMUM_SALARY)?->value;
                if ($current_upahBpjsKetenagakerjaan > $max_jp) $current_upahBpjsKetenagakerjaan = $max_jp;

                // bpjs kesehatan
                $company_percentageBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE)?->value;
                $employee_percentageBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE)?->value;

                $company_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($company_percentageBpjsKesehatan / 100);
                $employee_totalBpjsKesehatan = $current_upahBpjsKesehatan * ($employee_percentageBpjsKesehatan / 100);

                // jkk
                $company_percentageJkk = $company->npp?->jkk ?? 0;
                $company_totalJkk = $current_upahBpjsKetenagakerjaan * ($company_percentageJkk / 100);

                // jkm
                $company_percentageJkm = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JKM_PERCENTAGE)?->value;
                $company_totalJkm = $current_upahBpjsKetenagakerjaan * ($company_percentageJkm / 100);

                // jht
                $company_percentageJht = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JHT_PERCENTAGE)?->value;
                $employee_percentageJht = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE)?->value;
                $company_totalJht = $current_upahBpjsKetenagakerjaan * ($company_percentageJht / 100);
                $employee_totalJht = $current_upahBpjsKetenagakerjaan * ($employee_percentageJht / 100);

                // jp
                $company_percentageJp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::COMPANY_JP_PERCENTAGE)?->value;
                $employee_percentageJp = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::EMPLOYEE_JP_PERCENTAGE)?->value;

                $company_totalJp = $current_upahBpjsKetenagakerjaan * ($company_percentageJp / 100);
                $employee_totalJp = $current_upahBpjsKetenagakerjaan * ($employee_percentageJp / 100);

                // company = benefit (tidak perlu kalkulasi, hanya catat)
                // employee = deduction (kalkulasi)
                // dd([
                //     'company_totalBpjsKesehatan' => $company_totalBpjsKesehatan,
                //     'employee_totalBpjsKesehatan' => $employee_totalBpjsKesehatan,
                //     'company_totalJkk' => $company_totalJkk,
                //     'company_totalJkm' => $company_totalJkm,
                //     'company_totalJht' => $company_totalJht,
                //     'employee_totalJht' => $employee_totalJht,
                //     'company_totalJp' => $company_totalJp,
                //     'employee_totalJp' => $employee_totalJp,
                // ]);
                // end calculate bpjs

                foreach ($bpjsPayrollComponents as $bpjsPayrollComponent) {
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_BPJS_KESEHATAN)) $amount = $company_totalBpjsKesehatan;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_BPJS_KESEHATAN)) $amount = $employee_totalBpjsKesehatan;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKK)) $amount = $company_totalJkk;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JKM)) $amount = $company_totalJkm;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JHT)) $amount = $company_totalJht;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JHT)) $amount = $employee_totalJht;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::COMPANY_JP)) $amount = $company_totalJp;
                    if ($bpjsPayrollComponent->category->is(PayrollComponentCategory::EMPLOYEE_JP)) $amount = $employee_totalJp;

                    $amount = self::calculatePayrollComponentPeriodType($defaultPayrollComponent, $amount, $cutoffDiffDay, $runPayrollUser);

                    self::createComponent($runPayrollUser, $defaultPayrollComponent->id, $amount);
                }
            }

            // overtime payroll component
            $overtimePayrollComponent = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('category', PayrollComponentCategory::OVERTIME)->first();
            if ($overtimePayrollComponent) {
                // get overtime setting
                $overtime = $runPayrollUser->user->overtime;
                if (!$overtime) {
                    return response()->json([
                        'success' => false,
                        'data' => 'Please set overtime setting for each user before submit Run Payroll',
                    ]);
                }

                $amount = 0;

                switch ($overtime->rate_type) {
                    case RateType::AMOUNT:
                        $hourlyAmount = $overtime->rate_amount;

                        break;
                    case RateType::BASIC_SALARY:
                        $hourlyAmount = $userBasicSalary / $overtime->rate_amount;

                        break;
                    case RateType::ALLOWANCES:
                        $hourlyAmount = 0;

                        foreach ($overtime->overtimeAllowances as $overtimeAllowance) {
                            $hourlyAmount += $overtimeAllowance->payrollComponent?->amount > 0 ? ($overtimeAllowance->payrollComponent?->amount / $overtimeAllowance->amount) : 0;
                        }

                        break;
                    case RateType::FORMULA:
                        $hourlyAmount = FormulaService::calculate(user: $runPayrollUser->user, model: $overtime, formulas: $overtime->formulas, startPeriod: $cutoffAttendanceStartDate, endPeriod: $cutoffAttendanceEndDate);

                        break;
                    default:
                        $hourlyAmount = 0;

                        break;
                }

                // logic compensation_rate_per_day (currently we don't use that logic)

                // get overtime request
                $overtimeRequests = $runPayrollUser->user->overtimeRequests()->where('date', [$cutoffAttendanceStartDate, $cutoffAttendanceEndDate])->where('approval_status', ApprovalStatus::APPROVED)->get();

                foreach ($overtimeRequests as $overtimeRequest) {
                    // overtimme rounding
                    $overtimeDuration = $overtimeRequest->duration;
                    if ($overtimeRounding = $overtime->overtimeRoundings()->where('start_minute', '>=', $overtimeDuration)->where('end_minute', '<=', $overtimeDuration)->first()) {
                        $overtimeDuration = $overtimeRounding->rounded;
                    }

                    // overtime multiplier
                    foreach ($overtime->overtimeMultipliers()->where('is_weekday', Carbon::parse($overtimeRequest->date)->isWeekday())->orderBy('start_hour')->get() as $overtimeMultiplier) {
                        // break if there's no suitable data for minimum start_hour
                        if ($overtimeDuration < $overtimeMultiplier->start_hour) break;

                        for ($hour = 1; $hour <= $overtimeDuration; $hour++) {
                            if (($hour >= $overtimeMultiplier->start_hour) && ($hour <= $overtimeMultiplier->end_hour)) {
                                $multiply = $overtimeMultiplier->multiply;
                            } else {
                                $multiply = 1;
                            }

                            $amount += ($hourlyAmount * $multiply);
                        }
                    }
                }

                self::createComponent($runPayrollUser, $overtimePayrollComponent->id, $amount);
            }

            // insert other updated payroll component
            $updatePayrollComponent?->details()->whereNotIn('payroll_component_id', $defaultPayrollComponents->pluck('id')->toArray())->get()->map(function ($updatePayrollComponentDetail) use ($runPayrollUser) {
                self::createComponent($runPayrollUser, $updatePayrollComponentDetail->payroll_component_id, $updatePayrollComponentDetail->new_amount);
            });

            // other payroll component
            PayrollComponent::tenanted()->whereCompany($request['company_id'])->whereNotIn('id', $runPayrollUser->components()->pluck('payroll_component_id'))->get()->map(function ($otherPayrollComponent) use ($runPayrollUser, $cutoffDiffDay, $cutoffAttendanceStartDate, $cutoffAttendanceEndDate) {
                if ($otherPayrollComponent->amount == 0 && count($otherPayrollComponent->formulas)) {
                    $amount = FormulaService::calculate(user: $runPayrollUser->user, model: $otherPayrollComponent, formulas: $otherPayrollComponent->formulas, startPeriod: $cutoffAttendanceStartDate, endPeriod: $cutoffAttendanceEndDate);
                } else {
                    $amount = $otherPayrollComponent->amount;
                }

                $amount = self::calculatePayrollComponentPeriodType($otherPayrollComponent, $amount, $cutoffDiffDay, $runPayrollUser);

                self::createComponent($runPayrollUser, $otherPayrollComponent->id, $amount);
            });

            // update total amount for each user
            self::refreshRunPayrollUser($runPayrollUser);
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * create run payroll details
     *
     * @param  RunPayroll   $runPayroll
     * @param  string|int   $userId
     * @return RunPayrollUser
     */
    public static function assignUser(RunPayroll $runPayroll, string|int $userId): RunPayrollUser
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
    public static function createComponent(RunPayrollUser $runPayrollUser, int $payrollComponentId, int|float $amount = 0, ?bool $isEditable = true): RunPayrollUserComponent
    {
        return $runPayrollUser->components()->create([
            'payroll_component_id' => $payrollComponentId,
            'amount' => $amount,
            'is_editable' => $isEditable,
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
    public static function calculatePayrollComponentPeriodType(PayrollComponent $payrollComponent, int|float $amount = 0, int $cutoffDiffDay = 0, ?RunPayrollUser $runPayrollUser = null): int|float
    {
        switch ($payrollComponent->period_type) {
            case PayrollComponentPeriodType::DAILY:
                // rate_amount * cutoff diff days
                if (!$payrollComponent->formulas) $amount = $amount * $cutoffDiffDay;

                break;
            case PayrollComponentPeriodType::MONTHLY:
                $amount = $amount;

                break;
            case PayrollComponentPeriodType::ONE_TIME:
                if ($runPayrollUser->user->oneTimePayrollComponents()->firstWhere('payroll_component_id', $payrollComponent->id)) {
                    $amount = 0;
                } else {
                    $runPayrollUser->user->oneTimePayrollComponents()->create(['payroll_component_id' => $payrollComponent->id]);
                    $amount = $amount;
                }

                break;
            default:
                //

                break;
        }

        return $amount;
    }

    public static function refreshRunPayrollUser(RunPayrollUser|int $runPayrollUser)
    {
        if (!$runPayrollUser instanceof RunPayrollUser) {
            $runPayrollUser = RunPayrollUser::findOrFail($runPayrollUser);
        }

        $basicSalary = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('category', PayrollComponentCategory::BASIC_SALARY);
            $q->where('is_calculateable', true);
        })->sum('amount');

        $allowance = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::ALLOWANCE);
            $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            $q->where('is_calculateable', true);
        })->sum('amount');

        $additionalEarning = 0;

        $benefit = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::BENEFIT);
            $q->where('is_calculateable', true);
        })->sum('amount');

        $deduction = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
            $q->where('type', PayrollComponentType::DEDUCTION);
            $q->where('is_calculateable', true);
        })->sum('amount');

        $grossSalary = $basicSalary + $allowance + $additionalEarning + $deduction;
        $taxPercentage = 0;

        if (in_array($runPayrollUser->user->payrollInfo->ptkp_status, [PtkpStatus::TK_0, PtkpStatus::TK_1, PtkpStatus::K_0])) {
            if ($grossSalary <= 5400000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 5400000 && $grossSalary < 5650000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 5650000 && $grossSalary < 5950000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 5950000 && $grossSalary < 6300000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 6300000 && $grossSalary < 6750000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 6750000 && $grossSalary < 7500000) {
                $taxPercentage = 1.25;
            } else if ($grossSalary > 7500000 && $grossSalary < 8550000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 8550000 && $grossSalary < 9650000) {
                $taxPercentage = 1.75;
            } else if ($grossSalary > 9650000 && $grossSalary < 10050000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 10050000 && $grossSalary < 10350000) {
                $taxPercentage = 2.25;
            } else if ($grossSalary > 10350000 && $grossSalary < 10700000) {
                $taxPercentage = 2.5;
            } else if ($grossSalary > 10700000 && $grossSalary < 11050000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 11050000 && $grossSalary < 11600000) {
                $taxPercentage = 3.5;
            } else if ($grossSalary > 11600000 && $grossSalary < 12500000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 12500000 && $grossSalary < 13750000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 13750000 && $grossSalary < 15100000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 15100000 && $grossSalary < 16950000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 16950000 && $grossSalary < 19750000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 19750000 && $grossSalary < 24150000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 24150000 && $grossSalary < 26450000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 26450000 && $grossSalary < 28000000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 28000000 && $grossSalary < 30050000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 30050000 && $grossSalary < 32400000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 32400000 && $grossSalary < 35400000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 35400000 && $grossSalary < 39100000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 39100000 && $grossSalary < 43850000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 43850000 && $grossSalary < 47800000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 47800000 && $grossSalary < 51400000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 51400000 && $grossSalary < 56300000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 56300000 && $grossSalary < 62200000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 62200000 && $grossSalary < 68600000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 68600000 && $grossSalary < 77500000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 77500000 && $grossSalary < 89000000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 89000000 && $grossSalary < 103000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 103000000 && $grossSalary < 125000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 125000000 && $grossSalary < 157000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 157000000 && $grossSalary < 206000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 206000000 && $grossSalary < 337000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 337000000 && $grossSalary < 454000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 454000000 && $grossSalary < 550000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 550000000 && $grossSalary < 695000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 695000000 && $grossSalary < 910000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 910000000 && $grossSalary < 1400000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1400000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        if (in_array($runPayrollUser->user->payrollInfo->ptkp_status, [PtkpStatus::TK_2, PtkpStatus::TK_3, PtkpStatus::K_1, PtkpStatus::K_2])) {
            if ($grossSalary <= 6200000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 6200000 && $grossSalary < 6500000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 6500000 && $grossSalary < 6850000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 6850000 && $grossSalary < 7300000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 7300000 && $grossSalary < 9200000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 9200000 && $grossSalary < 10750000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 10750000 && $grossSalary < 11250000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 11250000 && $grossSalary < 11600000) {
                $taxPercentage = 2.5;
            } else if ($grossSalary > 11600000 && $grossSalary < 12600000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 12600000 && $grossSalary < 13600000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 13600000 && $grossSalary < 14950000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 14950000 && $grossSalary < 16400000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 16400000 && $grossSalary < 18450000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 18450000 && $grossSalary < 21850000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 21850000 && $grossSalary < 26000000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 26000000 && $grossSalary < 27700000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 27700000 && $grossSalary < 29350000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 29350000 && $grossSalary < 31450000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 31450000 && $grossSalary < 33950000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 33950000 && $grossSalary < 37100000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 37100000 && $grossSalary < 41100000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 41100000 && $grossSalary < 45800000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 45800000 && $grossSalary < 49500000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 49500000 && $grossSalary < 53800000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 53800000 && $grossSalary < 58500000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 58500000 && $grossSalary < 64000000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 64000000 && $grossSalary < 71000000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 71000000 && $grossSalary < 80000000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 80000000 && $grossSalary < 93000000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 93000000 && $grossSalary < 109000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 109000000 && $grossSalary < 129000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 129000000 && $grossSalary < 163000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 163000000 && $grossSalary < 211000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 211000000 && $grossSalary < 374000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 374000000 && $grossSalary < 459000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 459000000 && $grossSalary < 555000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 555000000 && $grossSalary < 704000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 704000000 && $grossSalary < 957000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 957000000 && $grossSalary < 1405000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1405000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        if (in_array($runPayrollUser->user->payrollInfo->ptkp_status, [PtkpStatus::K_3])) {
            if ($grossSalary <= 6600000) {
                $taxPercentage = 0;
            } else if ($grossSalary > 6600000 && $grossSalary < 6950000) {
                $taxPercentage = 0.25;
            } else if ($grossSalary > 6950000 && $grossSalary < 7350000) {
                $taxPercentage = 0.5;
            } else if ($grossSalary > 7350000 && $grossSalary < 7800000) {
                $taxPercentage = 0.75;
            } else if ($grossSalary > 7800000 && $grossSalary < 8850000) {
                $taxPercentage = 1;
            } else if ($grossSalary > 8850000 && $grossSalary < 9800000) {
                $taxPercentage = 1.25;
            } else if ($grossSalary > 9800000 && $grossSalary < 10950000) {
                $taxPercentage = 1.5;
            } else if ($grossSalary > 10950000 && $grossSalary < 11200000) {
                $taxPercentage = 1.75;
            } else if ($grossSalary > 11200000 && $grossSalary < 12050000) {
                $taxPercentage = 2;
            } else if ($grossSalary > 12050000 && $grossSalary < 12950000) {
                $taxPercentage = 3;
            } else if ($grossSalary > 12950000 && $grossSalary < 14150000) {
                $taxPercentage = 4;
            } else if ($grossSalary > 14150000 && $grossSalary < 15550000) {
                $taxPercentage = 5;
            } else if ($grossSalary > 15550000 && $grossSalary < 17050000) {
                $taxPercentage = 6;
            } else if ($grossSalary > 17050000 && $grossSalary < 19500000) {
                $taxPercentage = 7;
            } else if ($grossSalary > 19500000 && $grossSalary < 22700000) {
                $taxPercentage = 8;
            } else if ($grossSalary > 22700000 && $grossSalary < 26600000) {
                $taxPercentage = 9;
            } else if ($grossSalary > 26600000 && $grossSalary < 28100000) {
                $taxPercentage = 10;
            } else if ($grossSalary > 28100000 && $grossSalary < 30100000) {
                $taxPercentage = 11;
            } else if ($grossSalary > 30100000 && $grossSalary < 32600000) {
                $taxPercentage = 12;
            } else if ($grossSalary > 32600000 && $grossSalary < 35400000) {
                $taxPercentage = 13;
            } else if ($grossSalary > 35400000 && $grossSalary < 38900000) {
                $taxPercentage = 14;
            } else if ($grossSalary > 38900000 && $grossSalary < 43000000) {
                $taxPercentage = 15;
            } else if ($grossSalary > 43000000 && $grossSalary < 47400000) {
                $taxPercentage = 16;
            } else if ($grossSalary > 47400000 && $grossSalary < 51200000) {
                $taxPercentage = 17;
            } else if ($grossSalary > 51200000 && $grossSalary < 55800000) {
                $taxPercentage = 18;
            } else if ($grossSalary > 55800000 && $grossSalary < 60400000) {
                $taxPercentage = 19;
            } else if ($grossSalary > 60400000 && $grossSalary < 66700000) {
                $taxPercentage = 20;
            } else if ($grossSalary > 66700000 && $grossSalary < 74500000) {
                $taxPercentage = 21;
            } else if ($grossSalary > 74500000 && $grossSalary < 83200000) {
                $taxPercentage = 22;
            } else if ($grossSalary > 83200000 && $grossSalary < 95600000) {
                $taxPercentage = 23;
            } else if ($grossSalary > 95600000 && $grossSalary < 110000000) {
                $taxPercentage = 24;
            } else if ($grossSalary > 110000000 && $grossSalary < 134000000) {
                $taxPercentage = 25;
            } else if ($grossSalary > 134000000 && $grossSalary < 169000000) {
                $taxPercentage = 26;
            } else if ($grossSalary > 169000000 && $grossSalary < 221000000) {
                $taxPercentage = 27;
            } else if ($grossSalary > 221000000 && $grossSalary < 390000000) {
                $taxPercentage = 28;
            } else if ($grossSalary > 390000000 && $grossSalary < 463000000) {
                $taxPercentage = 29;
            } else if ($grossSalary > 463000000 && $grossSalary < 561000000) {
                $taxPercentage = 30;
            } else if ($grossSalary > 561000000 && $grossSalary < 709000000) {
                $taxPercentage = 31;
            } else if ($grossSalary > 709000000 && $grossSalary < 965000000) {
                $taxPercentage = 32;
            } else if ($grossSalary > 965000000 && $grossSalary < 1419000000) {
                $taxPercentage = 33;
            } else if ($grossSalary > 1419000000) {
                $taxPercentage = 34;
            } else {
                $taxPercentage = 0;
            }
        }

        $taxNominal = $grossSalary * ($taxPercentage / 100);

        $deduction = $deduction + $taxNominal;

        $runPayrollUser->update([
            'basic_salary' => $basicSalary,
            'allowance' => $allowance,
            'additional_earning' => $additionalEarning,
            'deduction' => $deduction,
            'benefit' => $benefit,
        ]);
    }
}


// if($grossSalary <= 5400000){
//     $taxPercentage = 0;
//  }else if($grossSalary > 5400000 && $grossSalary < 5650000){
//     $taxPercentage = 0.25;
//  }else if($grossSalary > 5650000 && $grossSalary < 5950000){
//     $taxPercentage = 0.5;
//  }else if($grossSalary > 5950000 && $grossSalary < 6300000){
//     $taxPercentage = 0.75;
//  }else if($grossSalary > 6300000 && $grossSalary < 6750000){
//     $taxPercentage = 1;
//  }else if($grossSalary > 6750000 && $grossSalary < 7500000){
//     $taxPercentage = 1.25;
//  }else if($grossSalary > 7500000 && $grossSalary < 8550000){
//     $taxPercentage = 1.5;
//  }else if($grossSalary > 8550000 && $grossSalary < 9650000){
//     $taxPercentage = 1.75;
//  }else if($grossSalary > 9650000 && $grossSalary < 10050000){
//     $taxPercentage = 2;
//  }else if($grossSalary > 10050000 && $grossSalary < 10350000){
//     $taxPercentage = 2.25;
//  }else if($grossSalary > 10350000 && $grossSalary < 10700000){
//     $taxPercentage = 2.5;
//  }else if($grossSalary > 10700000 && $grossSalary < 11050000){
//     $taxPercentage = 3;
//  }else if($grossSalary > 11050000 && $grossSalary < 11600000){
//     $taxPercentage = 3.5;
//  }else if($grossSalary > 11600000 && $grossSalary < 12500000){
//     $taxPercentage = 4;
//  }else if($grossSalary > 12500000 && $grossSalary < 13750000){
//     $taxPercentage = 5;
//  }else if($grossSalary > 13750000 && $grossSalary < 15100000){
//     $taxPercentage = 6;
//  }else if($grossSalary > 15100000 && $grossSalary < 16950000){
//     $taxPercentage = 7;
//  }else if($grossSalary > 16950000 && $grossSalary < 19750000){
//     $taxPercentage = 8;
//  }else if($grossSalary > 19750000 && $grossSalary < 24150000){
//     $taxPercentage = 9;
//  }else if($grossSalary > 24150000 && $grossSalary < 26450000){
//     $taxPercentage = 10;
//  }else if($grossSalary > 26450000 && $grossSalary < 28000000){
//     $taxPercentage = 11;
//  }else if($grossSalary > 28000000 && $grossSalary < 30050000){
//     $taxPercentage = 12;
//  }else if($grossSalary > 30050000 && $grossSalary < 32400000){
//     $taxPercentage = 13;
//  }else if($grossSalary > 32400000 && $grossSalary < 35400000){
//     $taxPercentage = 14;
//  }else if($grossSalary > 35400000 && $grossSalary < 39100000){
//     $taxPercentage = 15;
//  }else if($grossSalary > 39100000 && $grossSalary < 43850000){
//     $taxPercentage = 16;
//  }else if($grossSalary > 43850000 && $grossSalary < 47800000){
//     $taxPercentage = 17;
//  }else if($grossSalary > 47800000 && $grossSalary < 51400000){
//     $taxPercentage = 18;
//  }else if($grossSalary > 51400000 && $grossSalary < 56300000){
//     $taxPercentage = 19;
//  }else if($grossSalary > 56300000 && $grossSalary < 62200000){
//     $taxPercentage = 20;
//  }else if($grossSalary > 62200000 && $grossSalary < 68600000){
//     $taxPercentage = 21;
//  }else if($grossSalary > 68600000 && $grossSalary < 77500000){
//     $taxPercentage = 22;
//  }else if($grossSalary > 77500000 && $grossSalary < 89000000){
//     $taxPercentage = 23;
//  }else if($grossSalary > 89000000 && $grossSalary < 103000000){
//     $taxPercentage = 24;
//  }else if($grossSalary > 103000000 && $grossSalary < 125000000){
//     $taxPercentage = 25;
//  }else if($grossSalary > 125000000 && $grossSalary < 157000000){
//     $taxPercentage = 26;
//  }else if($grossSalary > 157000000 && $grossSalary < 206000000){
//     $taxPercentage = 27;
//  }else if($grossSalary > 206000000 && $grossSalary < 337000000){
//     $taxPercentage = 28;
//  }else if($grossSalary > 337000000 && $grossSalary < 454000000){
//     $taxPercentage = 29;
//  }else if($grossSalary > 454000000 && $grossSalary < 550000000){
//     $taxPercentage = 30;
//  }else if($grossSalary > 550000000 && $grossSalary < 695000000){
//     $taxPercentage = 31;
//  }else if($grossSalary > 695000000 && $grossSalary < 910000000){
//     $taxPercentage = 32;
//  }else if($grossSalary > 910000000 && $grossSalary < 1400000000){
//     $taxPercentage = 33;
//  }else if($grossSalary > 1400000000){
//     $taxPercentage = 34;
//  }else{
//     $taxPercentage = 0;
//  }

//  TABLE B
//  if($grossSalary <= 6200000){
//     $taxPercentage = 0;
//  }else if($grossSalary > 6200000 && $grossSalary < 6500000 ){
//     $taxPercentage = 0.25;
//  }else if($grossSalary > 6500000 && $grossSalary < 6850000 ){
//     $taxPercentage = 0.5;
//  }else if($grossSalary > 6850000 && $grossSalary < 7300000 ){
//     $taxPercentage = 0.75;
//  }else if($grossSalary > 7300000 && $grossSalary < 9200000 ){
//     $taxPercentage = 1;
//  }else if($grossSalary > 9200000 && $grossSalary < 10750000 ){
//     $taxPercentage = 1.5;
//  }else if($grossSalary > 10750000 && $grossSalary < 11250000 ){
//     $taxPercentage = 2;
//  }else if($grossSalary > 11250000 && $grossSalary < 11600000 ){
//     $taxPercentage = 2.5;
//  }else if($grossSalary > 11600000 && $grossSalary < 12600000 ){
//     $taxPercentage = 3;
//  }else if($grossSalary > 12600000 && $grossSalary < 13600000 ){
//     $taxPercentage = 4;
//  }else if($grossSalary > 13600000 && $grossSalary < 14950000 ){
//     $taxPercentage = 5;
//  }else if($grossSalary > 14950000 && $grossSalary < 16400000 ){
//     $taxPercentage = 6;
//  }else if($grossSalary > 16400000 && $grossSalary < 18450000 ){
//     $taxPercentage = 7;
//  }else if($grossSalary > 18450000 && $grossSalary < 21850000 ){
//     $taxPercentage = 8;
//  }else if($grossSalary > 21850000 && $grossSalary < 26000000 ){
//     $taxPercentage = 9;
//  }else if($grossSalary > 26000000 && $grossSalary < 27700000 ){
//     $taxPercentage = 10;
//  }else if($grossSalary > 27700000 && $grossSalary < 29350000 ){
//     $taxPercentage = 11;
//  }else if($grossSalary > 29350000 && $grossSalary < 31450000 ){
//     $taxPercentage = 12;
//  }else if($grossSalary > 31450000 && $grossSalary < 33950000 ){
//     $taxPercentage = 13;
//  }else if($grossSalary > 33950000 && $grossSalary < 37100000 ){
//     $taxPercentage = 14;
//  }else if($grossSalary > 37100000 && $grossSalary < 41100000 ){
//     $taxPercentage = 15;
//  }else if($grossSalary > 41100000 && $grossSalary < 45800000){
//     $taxPercentage = 16;
//  }else if($grossSalary > 45800000 && $grossSalary < 49500000){
//     $taxPercentage = 17;
//  }else if($grossSalary > 49500000 && $grossSalary < 53800000){
//     $taxPercentage = 18;
//  }else if($grossSalary > 53800000 && $grossSalary < 58500000){
//     $taxPercentage = 19;
//  }else if($grossSalary > 58500000 && $grossSalary < 64000000){
//     $taxPercentage = 20;
//  }else if($grossSalary > 64000000 && $grossSalary < 71000000){
//     $taxPercentage = 21;
//  }else if($grossSalary > 71000000 && $grossSalary < 80000000){
//     $taxPercentage = 22;
//  }else if($grossSalary > 80000000 && $grossSalary < 93000000){
//     $taxPercentage = 23;
//  }else if($grossSalary > 93000000 && $grossSalary < 109000000){
//     $taxPercentage = 24;
//  }else if($grossSalary > 109000000 && $grossSalary < 129000000){
//     $taxPercentage = 25;
//  }else if($grossSalary > 129000000 && $grossSalary < 163000000){
//     $taxPercentage = 26;
//  }else if($grossSalary > 163000000 && $grossSalary < 211000000){
//     $taxPercentage = 27;
//  }else if($grossSalary > 211000000 && $grossSalary < 374000000){
//     $taxPercentage = 28;
//  }else if($grossSalary > 374000000 && $grossSalary < 459000000){
//     $taxPercentage = 29;
//  }else if($grossSalary > 459000000 && $grossSalary < 555000000){
//     $taxPercentage = 30;
//  }else if($grossSalary > 555000000 && $grossSalary < 704000000){
//     $taxPercentage = 31;
//  }else if($grossSalary > 704000000 && $grossSalary < 957000000){
//     $taxPercentage = 32;
//  }else if($grossSalary > 957000000 && $grossSalary < 1405000000){
//     $taxPercentage = 33;
//  }else if($grossSalary > 1405000000){
//     $taxPercentage = 34;
//  }else{
//     $taxPercentage = 0;
//  }

//  TABLE C
//  if($grossSalary <= 6600000){
//     $taxPercentage = 0;
//  }else if($grossSalary > 6600000 && $grossSalary < 6950000){
//     $taxPercentage = 0.25;
//  }else if($grossSalary > 6950000 && $grossSalary < 7350000){
//     $taxPercentage = 0.5;
//  }else if($grossSalary > 7350000 && $grossSalary < 7800000){
//     $taxPercentage = 0.75;
//  }else if($grossSalary > 7800000 && $grossSalary < 8850000){
//     $taxPercentage = 1;
//  }else if($grossSalary > 8850000 && $grossSalary < 9800000){
//     $taxPercentage = 1.25;
//  }else if($grossSalary > 9800000 && $grossSalary < 10950000){
//     $taxPercentage = 1.5;
//  }else if($grossSalary > 10950000 && $grossSalary < 11200000){
//     $taxPercentage = 1.75;
//  }else if($grossSalary > 11200000 && $grossSalary < 12050000){
//     $taxPercentage = 2;
//  }else if($grossSalary > 12050000 && $grossSalary < 12950000){
//     $taxPercentage = 3;
//  }else if($grossSalary > 12950000 && $grossSalary < 14150000){
//     $taxPercentage = 4;
//  }else if($grossSalary > 14150000 && $grossSalary < 15550000){
//     $taxPercentage = 5;
//  }else if($grossSalary > 15550000 && $grossSalary < 17050000){
//     $taxPercentage = 6;
//  }else if($grossSalary > 17050000 && $grossSalary < 19500000){
//     $taxPercentage = 7;
//  }else if($grossSalary > 19500000 && $grossSalary < 22700000){
//     $taxPercentage = 8;
//  }else if($grossSalary > 22700000 && $grossSalary < 26600000){
//     $taxPercentage = 9;
//  }else if($grossSalary > 26600000 && $grossSalary < 28100000){
//     $taxPercentage = 10;
//  }else if($grossSalary > 28100000 && $grossSalary < 30100000){
//     $taxPercentage = 11;
//  }else if($grossSalary > 30100000 && $grossSalary < 32600000){
//     $taxPercentage = 12;
//  }else if($grossSalary > 32600000 && $grossSalary < 35400000){
//     $taxPercentage = 13;
//  }else if($grossSalary > 35400000 && $grossSalary < 38900000){
//     $taxPercentage = 14;
//  }else if($grossSalary > 38900000 && $grossSalary < 43000000){
//     $taxPercentage = 15;
//  }else if($grossSalary > 43000000 && $grossSalary < 47400000){
//     $taxPercentage = 16;
//  }else if($grossSalary > 47400000 && $grossSalary < 51200000){
//     $taxPercentage = 17;
//  }else if($grossSalary > 51200000 && $grossSalary < 55800000){
//     $taxPercentage = 18;
//  }else if($grossSalary > 55800000 && $grossSalary < 60400000){
//     $taxPercentage = 19;
//  }else if($grossSalary > 60400000 && $grossSalary < 66700000){
//     $taxPercentage = 20;
//  }else if($grossSalary > 66700000 && $grossSalary < 74500000){
//     $taxPercentage = 21;
//  }else if($grossSalary > 74500000 && $grossSalary < 83200000){
//     $taxPercentage = 22;
//  }else if($grossSalary > 83200000 && $grossSalary < 95600000){
//     $taxPercentage = 23;
//  }else if($grossSalary > 95600000 && $grossSalary < 110000000){
//     $taxPercentage = 24;
//  }else if($grossSalary > 110000000 && $grossSalary < 134000000){
//     $taxPercentage = 25;
//  }else if($grossSalary > 134000000 && $grossSalary < 169000000){
//     $taxPercentage = 26;
//  }else if($grossSalary > 169000000 && $grossSalary < 221000000){
//     $taxPercentage = 27;
//  }else if($grossSalary > 221000000 && $grossSalary < 390000000){
//     $taxPercentage = 28;
//  }else if($grossSalary > 390000000 && $grossSalary < 463000000){
//     $taxPercentage = 29;
//  }else if($grossSalary > 463000000 && $grossSalary < 561000000){
//     $taxPercentage = 30;
//  }else if($grossSalary > 561000000 && $grossSalary < 709000000){
//     $taxPercentage = 31;
//  }else if($grossSalary > 709000000 && $grossSalary < 965000000){
//     $taxPercentage = 32;
//  }else if($grossSalary > 965000000 && $grossSalary < 1419000000){
//     $taxPercentage = 33;
//  }else if($grossSalary > 1419000000){
//     $taxPercentage = 34;
//  }else{
//     $taxPercentage = 0;
//  }

        // // calculate bpjs
        // if ($runPayrollUser->user->userBpjs) {
        //     // init bpjs variable
        //     $current_upahBpjsKesehatan = $runPayrollUser->user->userBpjs->upah_bpjs_kesehatan;
        //     $max_upahBpjsKesehatan = $company->countryTable->countrySettings()->firstWhere('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)?->value;
        //     if ($current_upahBpjsKesehatan > $max_upahBpjsKesehatan) $current_upahBpjsKesehatan = $max_upahBpjsKesehatan;


        //     $current_upahBpjsKetenagakerjaan = $runPayrollUser->user->userBpjs->upah_bpjs_ketenagakerjaan;
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
        //     dd([
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
