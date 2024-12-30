<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\FormulaAmountType;
use App\Enums\FormulaComponentEnum;
use App\Enums\PayrollComponentType;
use App\Models\Formula;
use App\Models\Overtime;
use App\Models\PayrollComponent;
use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class FormulaService
{
    /**
     * sync formula with related model
     *
     * @param  array|null  $formulas
     * @param  Formula|null  $parent,  used for insert recursively (nested $formulas)
     */
    public static function sync(Model $model, ?array $formulas = [], ?Formula $parent = null): void
    {
        // delete all existing formulas
        self::destroy($model);

        // create new formula
        if ($formulas) self::create($model, $formulas, $parent);
    }

    /**
     * destroy formula from related model
     */
    public static function destroy(Model $model): void
    {
        foreach ($model->formulas as $formula) {
            Schema::disableForeignKeyConstraints();

            $formula->formulaComponents()->delete();
            $formula->delete();

            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * sync formula with related model
     *
     * @param  Formula|null  $parent,  used for insert recursively (nested $formulas)
     */
    public static function create(Model $model, array $formulas, ?Formula $parent = null): void
    {
        foreach ($formulas as $formula) {
            if (isset($formula['child']) && is_array($formula['child'])) {
                $newFormula = $model->formulas()->create([
                    'parent_id' => $parent->id ?? null,
                    'component' => $formula['component'],
                    'amount' => $formula['amount'] ?? null,
                ]);

                // nested array
                self::create($model, $formula['child'], $newFormula);
            } else {
                $newFormula = $model->formulas()->create([
                    'parent_id' => $parent->id ?? null,
                    'component' => $formula['component'],
                    'amount' => $formula['amount'],
                ]);
            }

            // create the component of it's formula
            collect(explode(',', $formula['value']))->each(function ($formulaValue) use ($newFormula) {
                $newFormula->formulaComponents()->create([
                    'value' => $formulaValue,
                ]);
            });
        }
    }

    /**
     * count formula amount
     *
     * @param  User         $user
     * @param  Model        $model
     * @param  Collection   $formulas
     * @param  float        $amount
     * @param  string       $startPeriod
     * @param  string       $endPeriod
     */
    public static function calculate(User $user, Model $model, Collection $formulas, float $amount = 0, string|DateTime $startPeriod = null, string|DateTime $endPeriod = null)
    {
        if (!is_null($startPeriod)) $startPeriod = date('Y-m-d', strtotime($startPeriod));
        if (!is_null($endPeriod)) $endPeriod = date('Y-m-d', strtotime($endPeriod));

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
                                case DailyAttendance::ALPA:
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
                    // case FormulaComponentEnum::HOLIDAY:
                    //     //

                    //     break;
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
                if ($nextChild) $amount = self::calculate(user: $user, model: $model, formulas: $formula->child, amount: $amount, startPeriod: $startPeriod, endPeriod: $endPeriod);

                // skip current loop and continue to the next loop
                continue;
            } else {
                switch ($formula->component) {
                    case FormulaComponentEnum::DAILY_ATTENDANCE:
                        foreach ($formula->formulaComponents as $formulaComponent) {
                            switch ($formulaComponent->value) {
                                case DailyAttendance::PRESENT->value:
                                    $presentAttendance = AttendanceService::getTotalPresent($user, $startPeriod, $endPeriod);
                                    $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod) * $presentAttendance;
                                    break;
                                case DailyAttendance::ALPA->value:
                                    $alphaAttendance = AttendanceService::getTotalAlpa($user, $startPeriod, $endPeriod);
                                    $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod) * $alphaAttendance;

                                    break;
                                default:
                                    //

                                    break;
                            }
                        }

                        break;
                    case FormulaComponentEnum::SHIFT:
                        $totalAttendance = AttendanceService::getTotalAttendanceInShifts($user, $startPeriod, $endPeriod, $formula->formulaComponents->pluck('value')?->toArray() ?? []);
                        $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod) * $totalAttendance;
                        break;
                    case FormulaComponentEnum::BRANCH:
                        if (self::matchComponentValue($formula, $user->branch_id)) {
                            $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);
                        }

                        break;
                    // case FormulaComponentEnum::HOLIDAY:
                    //     $totalEvent = EventService::countTotalDateInPeriods($user, $startPeriod, $endPeriod, $formula->formulaComponents->pluck('value')?->toArray() ?? []);
                    //     $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod) * $totalEvent;

                    //     break;
                    case FormulaComponentEnum::EMPLOYEMENT_STATUS:
                        if (self::matchComponentValue($formula, $user->detail?->employment_status)) {
                            $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);
                        }

                        break;
                    case FormulaComponentEnum::JOB_POSITION:
                        if (self::matchComponentValue($formula, $user->detail?->job_position)) {
                            $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);
                        }

                        break;
                    case FormulaComponentEnum::GENDER:
                        if (self::matchComponentValue($formula, $user->gender)) {
                            $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);
                        }

                        break;
                    case FormulaComponentEnum::RELIGION:
                        if (self::matchComponentValue($formula, $user->detail?->religion)) {
                            $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);
                        }

                        break;
                    case FormulaComponentEnum::MARITAL_STATUS:
                        if (self::matchComponentValue($formula, $user->detail?->marital_status)) {
                            $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);
                        }

                        break;
                    case FormulaComponentEnum::ELSE:
                        $amount = self::sumAmount($model, $formula, $amount, $user, $startPeriod, $endPeriod);

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
     * @param  PayrollComponent|Overtime $model
     * @param  Formula                   $formula
     * @param  int|float                 $oldAmount
     */
    public static function sumAmount(PayrollComponent|Overtime $model, Formula $formula, int|float $oldAmount, ?User $user = null, string|DateTime $startPeriod, string|DateTime $endPeriod): int|float
    {
        $incomingAmount = $formula->amount;
        switch ($formula->amount_type) {
            case FormulaAmountType::SALARY_PER_SCHEDULE_CALENDAR_DAY:
                $totalWorkingDays = ScheduleService::getTotalWorkingDaysInPeriod($user, $startPeriod, $endPeriod);
                if ($totalWorkingDays > 0) {
                    $incomingAmount = ($user?->payrollInfo?->basic_salary ?? 0) / $totalWorkingDays;
                } else {
                    $incomingAmount = 0;
                }
                break;
            case FormulaAmountType::FULL_SALARY:
                $incomingAmount = $user?->payrollInfo?->basic_salary ?? 0;
                break;
            case FormulaAmountType::HALF_OF_SALARY:
                $incomingAmount = ($user?->payrollInfo?->basic_salary ?? 0) / 2;
                break;
            default:
                $incomingAmount = $formula->amount;
                break;
        }

        if ($model instanceof PayrollComponent && $model->type->is(PayrollComponentType::DEDUCTION)) {
            $incomingAmount = -abs($incomingAmount);
        }

        $newAmount = $oldAmount + $incomingAmount;

        return $newAmount;
    }
}
