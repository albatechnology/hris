<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DailyAttendance;
use App\Enums\EmploymentStatus;
use App\Enums\FormulaComponentEnum;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Enums\RateType;
use App\Enums\RunPayrollStatus;
use App\Models\Formula;
use App\Models\Overtime;
use App\Models\OvertimeRequest;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\UpdatePayrollComponent;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
            if (!$runPayrollDetail->getData()?->success) return response()->json($runPayrollDetail->getData());

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
        return RunPayroll::create([
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
     */
    public static function createDetails(RunPayroll $runPayroll, array $request): JsonResponse
    {
        // dump($request);
        // dump($runPayroll);
        // dummy companyid
        $payrollSetting = PayrollSetting::whereCompany($request['company_id'])->first();
        if (!$payrollSetting->cutoff_attendance_start_date || !$payrollSetting->cutoff_attendance_end_date) {
            return response()->json([
                'success' => false,
                'data' => 'Please set your Payroll Setting before submit Run Payroll',
            ]);
        }
        // dump($payrollSetting);
        $cutoffAttendanceStartDate = Carbon::parse($payrollSetting->cutoff_attendance_start_date . '-' . $request['period']);
        $cutoffAttendanceEndDate = Carbon::parse($payrollSetting->cutoff_attendance_end_date . '-' . $request['period'])->addMonth(1);
        $cutoffDiffDay = $cutoffAttendanceStartDate->diff($cutoffAttendanceEndDate)->days - 1;
        // dump($cutoffDiffDay);
        foreach (explode(',', $request['user_ids']) as $userId) {
            $runPayrollUser = self::assignUser($runPayroll, $userId);
            // dump($runPayrollUser);
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
            // dump($updatePayrollComponent);

            // define user basic salary
            $userBasicSalary = $runPayrollUser->user->payrollInfo?->basic_salary;
            // dump($userBasicSalary);

            // default payroll component
            $defaultPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('is_default', true)->whereNotIn('category', [PayrollComponentCategory::OVERTIME])->get();
            // dump($defaultPayrollComponents);
            foreach ($defaultPayrollComponents as $defaultPayrollComponent) {
                // dump($defaultPayrollComponent);
                // check if payroll component is updated on UpdatePayrollComponent::class
                $updatePayrollComponentDetail = $updatePayrollComponent?->details()->where('payroll_component_id', $defaultPayrollComponent->id)->first();
                if ($updatePayrollComponentDetail) {
                    // dump('if');
                    $amount = $updatePayrollComponentDetail->new_amount;

                    // override $userBasicSalary if there's an updated data on UpdatePayrollComponent::class
                    if ($updatePayrollComponentDetail->payrollComponent->category->is(PayrollComponentCategory::BASIC_SALARY)) $userBasicSalary = $amount;
                } else {
                    // dump('else');
                    // if the default amount is empty || 0
                    if ($defaultPayrollComponent->amount == 0 && count($defaultPayrollComponent->formulas)) {
                        $amount = FormulaService::calculate(user: $runPayrollUser->user, model: $defaultPayrollComponent, formulas: $defaultPayrollComponent->formulas);
                    } else {
                        $amount = $defaultPayrollComponent->amount;
                    }
                    // dump($amount);
                }

                switch ($defaultPayrollComponent->period_type) {
                    case PayrollComponentPeriodType::DAILY:
                        // rate_amount * cutoff diff days
                        if (!$defaultPayrollComponent->formulas) $amount = $amount * $cutoffDiffDay;

                        break;
                    case PayrollComponentPeriodType::MONTHLY:
                        $amount = $amount;

                        break;
                    case PayrollComponentPeriodType::ONE_TIME:
                        if ($runPayrollUser->user->oneTimePayrollComponents()->firstWhere('payroll_component_id', $defaultPayrollComponent->id)) {
                            $amount = 0;
                        } else {
                            $runPayrollUser->user->oneTimePayrollComponents()->create(['payroll_component_id' => $defaultPayrollComponent->id]);
                            $amount = $amount;
                        }

                        break;
                    default:
                        //

                        break;
                }
                // dump($amount);
                self::createComponent($runPayrollUser, $defaultPayrollComponent->id, $amount);
            }
            // dump('end default paryroll component');

            // overtime payroll component
            $overtimePayrollComponent = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('category', PayrollComponentCategory::OVERTIME)->first();
            if ($overtimePayrollComponent) {
                // get overtime setting
                $overtime = $runPayrollUser->user->overtime;
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

                        break;
                    case RateType::FORMULA:
                        // formula will be counted on the next code below
                        $hourlyAmount = 0;

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

                    if ($overtime->rate_type->is(RateType::FORMULA)) $hourlyAmount = FormulaService::calculate(user: $runPayrollUser->user, model: $overtime, formulas: $overtime->formulas);

                    // overtime multiplier
                    foreach ($overtime->overtimeMultipliers()->where('is_weekday', Carbon::parse($overtimeRequest->date)->isWeekday())->orderBy('start_hour')->get() as $overtimeMultiplier) {
                        // break if there's no suitable data for minimum start_hour
                        if ($overtimeDuration < $overtimeMultiplier->start_hour) break;

                        for ($hour = 1; $hour <= $overtimeDuration; $hour++) {
                            if ($hour >= $overtimeMultiplier->start_hour && $hour <= $overtimeMultiplier->end_hour) {
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
            // dump($updatePayrollComponent);
            // dump('sampeee');
            // other payroll component
            PayrollComponent::tenanted()->whereCompany($request['company_id'])->whereNotIn('id', $runPayrollUser->components()->pluck('payroll_component_id'))->get()->map(function ($otherPayrollComponent) use ($runPayrollUser, $cutoffDiffDay) {
                if ($otherPayrollComponent->amount == 0 && count($otherPayrollComponent->formulas)) {
                    // dump('ada formula');
                    $amount = FormulaService::calculate(user: $runPayrollUser->user, model: $otherPayrollComponent, formulas: $otherPayrollComponent->formulas);
                } else {
                    // dump('ga ada formula');
                    $amount = $otherPayrollComponent->amount;
                }
                // dd($amount);

                switch ($otherPayrollComponent->period_type) {
                    case PayrollComponentPeriodType::DAILY:
                        // rate_amount * cutoff diff days
                        if (!$otherPayrollComponent->formulas) $amount = $amount * $cutoffDiffDay;

                        break;
                    case PayrollComponentPeriodType::MONTHLY:
                        $amount = $amount;

                        break;
                    case PayrollComponentPeriodType::ONE_TIME:
                        if ($runPayrollUser->user->oneTimePayrollComponents()->firstWhere('payroll_component_id', $otherPayrollComponent->id)) {
                            $amount = 0;
                        } else {
                            $runPayrollUser->user->oneTimePayrollComponents()->create(['payroll_component_id' => $otherPayrollComponent->id]);
                            $amount = $amount;
                        }

                        break;
                    default:
                        //

                        break;
                }

                self::createComponent($runPayrollUser, $otherPayrollComponent->id, $amount);
            });

            // update total amount for each user
            $basicSalary = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
                $q->where('category', PayrollComponentCategory::BASIC_SALARY);
            })->sum('amount');

            $allowance = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
                $q->where('type', PayrollComponentType::ALLOWANCE);
                $q->whereNotIn('category', [PayrollComponentCategory::BASIC_SALARY]);
            })->sum('amount');

            $additionalEarning = 0;

            $deduction = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
                $q->where('type', PayrollComponentType::DEDUCTION);
            })->sum('amount');

            $benefit = $runPayrollUser->components()->whereHas('payrollComponent', function ($q) {
                $q->where('type', PayrollComponentType::BENEFIT);
            })->sum('amount');

            $runPayrollUser->update([
                'basic_salary' => $basicSalary,
                'allowance' => $allowance,
                'additional_earning' => $additionalEarning,
                'deduction' => $deduction,
                'benefit' => $benefit,
            ]);
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
     *
     */
    public static function assignUser(RunPayroll $runPayroll, string|int $userId): RunPayrollUser
    {
        return $runPayroll->users()->create(['user_id' => $userId]);
    }

    /**
     * sync formula with related model
     *
     * @param  RunPayrollUser   $runPayrollUser
     * @param  string|int  $userId
     */
    public static function createComponent(RunPayrollUser $runPayrollUser, int $payrollComponentId, int|float $amount = 0, ?bool $isEditable = true): RunPayrollUserComponent
    {
        return $runPayrollUser->components()->create([
            'payroll_component_id' => $payrollComponentId,
            'amount' => $amount,
            'is_editable' => $isEditable,
        ]);
    }
}
