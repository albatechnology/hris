<?php

namespace App\Services;

use App\Enums\DailyAttendance;
use App\Enums\FormulaComponentEnum;
use App\Enums\PayrollComponentType;
use App\Models\Formula;
use App\Models\PayrollComponent;
use App\Models\User;
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
     */
    public static function calculate(User $user, Model $model, Collection $formulas, float $amount = 0)
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
                if ($nextChild) $amount = self::calculate($user, $model, $formula->child, $amount);

                // skip current loop and continue to the next loop
                continue;
            } else {
                switch ($formula->component) {
                    case FormulaComponentEnum::DAILY_ATTENDANCE:
                        foreach ($formula->formulaComponents as $formulaComponent) {
                            switch ($formulaComponent->component) {
                                case DailyAttendance::PRESENT:
                                    $presentAttendance = 12;
                                    $amount = self::sumAmount($model, $amount, $formula->amount) * $presentAttendance;

                                    break;
                                case DailyAttendance::ALPHA:
                                    $alphaAttendance = 5;
                                    $amount = self::sumAmount($model, $amount, $formula->amount) * $alphaAttendance;

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
                            $amount = self::sumAmount($model, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::HOLIDAY:
                        //

                        break;
                    case FormulaComponentEnum::EMPLOYEMENT_STATUS:
                        if (self::matchComponentValue($formula, $user->detail?->employment_status)) {
                            $amount = self::sumAmount($model, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::JOB_POSITION:
                        if (self::matchComponentValue($formula, $user->detail?->job_position)) {
                            $amount = self::sumAmount($model, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::GENDER:
                        if (self::matchComponentValue($formula, $user->gender)) {
                            $amount = self::sumAmount($model, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::RELIGION:
                        if (self::matchComponentValue($formula, $user->detail?->religion)) {
                            $amount = self::sumAmount($model, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::MARITAL_STATUS:
                        if (self::matchComponentValue($formula, $user->detail?->marital_status)) {
                            $amount = self::sumAmount($model, $amount, $formula->amount);
                        }

                        break;
                    case FormulaComponentEnum::ELSE:
                        $amount = self::sumAmount($model, $amount, $formula->amount);

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
     * @param  Model            $model
     * @param  int|float        $oldAmount
     * @param  int|float        $incomingAmount
     */
    public static function sumAmount(Model $model, int|float $oldAmount, int|float $incomingAmount): int|float
    {
        if ($model instanceof PayrollComponent && $model->type->is(PayrollComponentType::DEDUCTION)) $incomingAmount = -abs($incomingAmount);

        $newAmount = $oldAmount + $incomingAmount;

        return $newAmount;
    }
}
