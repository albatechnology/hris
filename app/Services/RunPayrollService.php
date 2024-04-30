<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\EmploymentStatus;
use App\Enums\FormulaComponentEnum;
use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentType;
use App\Enums\RateType;
use App\Models\Formula;
use App\Models\PayrollComponent;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Models\UpdatePayrollComponent;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
            $defaultPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('is_default', true)->whereNotIn('category', [PayrollComponentCategory::OVERTIME])->get();
            foreach ($defaultPayrollComponents as $defaultPayrollComponent) {
                // check if payroll component is updated on UpdatePayrollComponent::class
                $updatePayrollComponentDetail = $updatePayrollComponent?->details()->where('payroll_component_id', $defaultPayrollComponent->id)->first();
                if ($updatePayrollComponentDetail) {
                    $amount = $updatePayrollComponentDetail->new_amount;
                } else {
                    // if the default amount is empty || 0
                    if ($defaultPayrollComponent->amount == 0) {
                        // WARNING
                        $amount = FormulaService::calculate($runPayrollUser->user, $defaultPayrollComponent, $defaultPayrollComponent->formulas);
                    } else {
                        $amount = $defaultPayrollComponent->amount;
                    }
                }
                self::createComponent($runPayrollUser, $defaultPayrollComponent->payroll_component_id, $amount);
            }

            // overtime payroll component
            $overtimePayrollComponent = PayrollComponent::tenanted()->whereCompany($request['company_id'])->where('category', PayrollComponentCategory::OVERTIME)->first();
            if ($overtimePayrollComponent) {
                $overtime = $runPayrollUser->user->overtime;
                switch ($overtime->rate_type) {
                    case RateType::AMOUNT:
                        $amount = $overtime->rate_amount;

                        break;
                    case RateType::BASIC_SALARY:
                        $amount = $runPayrollUser->user->payrollInfo?->basic_salary ?? 0;

                        break;
                    case RateType::AMOUNT:
                        $amount = $overtime->rate_amount;

                        break;
                    case RateType::FORMULA:
                        $amount = FormulaService::calculate($runPayrollUser->user, $overtime, $overtime->formulas);

                        break;
                    default:
                        $amount = 0;

                        break;
                }

                // $amount is not multiplied yet (OvertimeMultiplier::class)

                self::createComponent($runPayrollUser, $overtimePayrollComponent->id, $amount);
            }

            // insert other updated payroll component
            $updatePayrollComponent?->details()->whereNotIn('payroll_component_id', $defaultPayrollComponents->pluck('id')->toArray())->get()->map(function ($updatePayrollComponentDetail) use ($runPayrollUser) {
                self::createComponent($runPayrollUser, $updatePayrollComponentDetail->payroll_component_id, $updatePayrollComponentDetail->new_amount);
            });

            // other payroll component
            PayrollComponent::tenanted()->whereCompany($request['company_id'])->whereNotIn('id', $runPayrollUser->components()->pluck('payroll_component_id'))->get()->map(function ($otherPayrollComponent) use ($runPayrollUser) {
                if ($otherPayrollComponent->amount == 0) {
                    $amount = FormulaService::calculate($runPayrollUser->user, $otherPayrollComponent, $otherPayrollComponent->formulas);
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
}
