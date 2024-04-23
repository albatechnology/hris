<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\EmploymentStatus;
use App\Enums\FormulaComponentEnum;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Models\Formula;
use App\Models\PayrollComponent;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\UpdatePayrollComponent;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RunPayrollService
{
    /**
     * execute run payroll
     *
     * @param  array $request
     */
    public static function execute(array $request): RunPayroll | Exception
    {
        DB::beginTransaction();
        try {
            $runPayroll = self::createRunPayroll($request);
            self::createDetails($runPayroll, $request);

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
        return RunPayroll::create($request);
    }

    /**
     * create run payroll details
     * 
     * @param  RunPayroll   $runPayroll
     * @param  Request      $request
     */
    public static function createDetails(RunPayroll $runPayroll, array $request): void
    {
        foreach (explode(',', $request['user_ids']) as $userId) {
            $amount = 0;
            $runPayrollUser = self::assignUser($runPayroll, $userId);

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

            // default payroll component
            $defaultPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('is_default', true)->get();
            foreach ($defaultPayrollComponents as $defaultPayrollComponent) {
                // check if payroll component is updated on UpdatePayrollComponent::class
                $updatePayrollComponentDetail = $updatePayrollComponent?->details()->where('payroll_component_id', $defaultPayrollComponent->id)->first();
                if ($updatePayrollComponentDetail) {
                    $amount += $updatePayrollComponentDetail->new_amount;
                } else {
                    // if the default amount is empty || 0
                    if ($defaultPayrollComponent->amount == 0) {
                        // WARNING
                        $amount += self::calculateFormulas($runPayrollUser->user, $defaultPayrollComponent, $defaultPayrollComponent->formulas);
                    } else {
                        $amount += $defaultPayrollComponent->amount;
                    }
                }
                self::createComponent($runPayrollUser, $defaultPayrollComponent->payroll_component_id, $amount);
            }

            // insert other updated payroll component
            $updatePayrollComponent?->details()->whereNotIn('payroll_component_id', $defaultPayrollComponents->pluck('id')->toArray())->get()->map(function ($updatePayrollComponentDetail) use ($runPayrollUser, &$amount) {
                $amount += $updatePayrollComponentDetail->new_amount;
                self::createComponent($runPayrollUser, $updatePayrollComponentDetail->payroll_component_id, $updatePayrollComponentDetail->new_amount);
            });

            // other payroll component
            PayrollComponent::tenanted()->whereCompany($request['company_id'])->whereNotIn('id', $runPayrollUser->components()->pluck('payroll_component_id'))->get()->map(function ($otherPayrollComponent) use ($runPayrollUser, &$amount) {
                if ($otherPayrollComponent->amount == 0) {
                    $amount += self::calculateFormulas($runPayrollUser->user, $otherPayrollComponent, $otherPayrollComponent->formulas);
                } else {
                    $amount = $otherPayrollComponent->amount;
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

    /**
     * count formula amount
     *
     * @param  User         $user
     * @param  Collection   $formulas
     * @param  float        $amount
     */
    public static function calculateFormulas(User $user, PayrollComponent $payrollComponent, Collection $formulas, float $amount = 0)
    {
        foreach ($formulas as $formula) {
            if (count($formula->child)) {
                $nextChild = false;

                switch ($formula->component) {
                    case FormulaComponentEnum::DAILY_ATTENDANCE:
                        foreach ($formula->formulaComponents as $formulaComponent) {
                            switch ($formulaComponent->component) {
                                case DailyAttendance::PRESENT:
                                    $nextChild = true;

                                    break;
                                case DailyAttendance::ALPHA:
                                    $nextChild = true;

                                    break;
                                default:
                                    //

                                    break;
                            }
                        }

                        break;
                    case FormulaComponentEnum::SHIFT:
                        //

                        break;
                    case FormulaComponentEnum::BRANCH:
                        $nextChild = self::matchComponentValue($formula, $user->branch_id);

                        break;
                    case FormulaComponentEnum::HOLIDAY:
                        //

                        break;
                    case FormulaComponentEnum::EMPLOYEMENT_STATUS:
                        $nextChild = self::matchComponentValue($formula, $user->detail?->job_position);

                        break;
                    case FormulaComponentEnum::JOB_POSITION:
                        $nextChild = self::matchComponentValue($formula, $user->detail?->job_position);

                        break;
                    case FormulaComponentEnum::GENDER:
                        $nextChild = self::matchComponentValue($formula, $user->gender);

                        break;
                    case FormulaComponentEnum::RELIGION:
                        $nextChild = self::matchComponentValue($formula, $user->detail?->religion);

                        break;
                    case FormulaComponentEnum::MARITAL_STATUS:
                        $nextChild = self::matchComponentValue($formula, $user->detail?->marital_status);

                        break;
                    case FormulaComponentEnum::ELSE:
                        //

                        break;
                    default:
                        //

                        break;
                }

                // go to next  child
                if ($nextChild) $amount = self::calculateFormulas($user, $payrollComponent, $formula->child, $amount);

                // skip current loop and continue to the next loop
                continue;
            } else {
                switch ($formula->component) {
                    case FormulaComponentEnum::DAILY_ATTENDANCE:
                        foreach ($formula->formulaComponents as $formulaComponent) {
                            switch ($formulaComponent->component) {
                                case DailyAttendance::PRESENT:
                                    $presentAttendance = 12;
                                    $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount) * $presentAttendance;

                                    break;
                                case DailyAttendance::ALPHA:
                                    $alphaAttendance = 5;
                                    $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount) * $alphaAttendance;

                                    break;
                                default:
                                    //

                                    break;
                            }
                        }

                        break;
                    case FormulaComponentEnum::SHIFT:
                        //

                        break;
                    case FormulaComponentEnum::BRANCH:
                        if (self::matchComponentValue($formula, $user->branch_id)) {
                            $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::HOLIDAY:
                        //

                        break;
                    case FormulaComponentEnum::EMPLOYEMENT_STATUS:
                        if (self::matchComponentValue($formula, $user->detail?->employment_status)) {
                            $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::JOB_POSITION:
                        if (self::matchComponentValue($formula, $user->detail?->job_position)) {
                            $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::GENDER:
                        if (self::matchComponentValue($formula, $user->gender)) {
                            $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::RELIGION:
                        if (self::matchComponentValue($formula, $user->detail?->religion)) {
                            $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::MARITAL_STATUS:
                        if (self::matchComponentValue($formula, $user->detail?->marital_status)) {
                            $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::ELSE:
                        $amount = self::calculateAmount($payrollComponent, $amount, $formula->amount);

                        break;
                    default:
                        //

                        break;
                }
            }
        }

        return $amount;
    }

    /**
     * sync formula with related model
     *
     * @param  Formula                      $formula
     * @param  \BackedEnum|string|int|float $value
     */
    public static function matchComponentValue(Formula $formula, \BackedEnum|string|int|float $value = null): bool
    {
        if ($value instanceof \BackedEnum) $value = $value->value;

        $formulaComponent = $formula->formulaComponents->where('value', $value)->first();

        return !is_null($formulaComponent);
    }

    /**
     * sync formula with related model
     *
     * @param  PayrollComponent $payrollcomponent
     * @param  int|float        $oldAmount
     * @param  int|float        $incomingAmount
     */
    public static function calculateAmount(PayrollComponent $payrollComponent, int|float $oldAmount, int|float $incomingAmount): int|float
    {
        if ($payrollComponent->type->is(PayrollComponentType::DEDUCTION)) $incomingAmount = -abs($incomingAmount);

        $newAmount = $oldAmount + $incomingAmount;

        return $newAmount;
    }
}
