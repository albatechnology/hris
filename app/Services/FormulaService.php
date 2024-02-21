<?php

namespace App\Services;

use App\Models\Formula;
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
    public static function sync(Model $model, array $formulas = [], ?Formula $parent = null): void
    {
        // delete all existing formulas
        self::destroy($model);

        // create new formula
        self::create($model, $formulas, $parent);
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
}
