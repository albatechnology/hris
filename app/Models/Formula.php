<?php

namespace App\Models;

use App\Enums\FormulaAmountType;
use App\Enums\FormulaComponentEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formula extends BaseModel
{
    protected $fillable = [
        'formulaable_type',
        'formulaable_id',
        'parent_id',
        'component',
        'amount_type',
        'amount',
    ];

    protected $casts = [
        'formulaable_type' => 'string',
        'formulaable_id' => 'integer',
        'parent_id' => 'integer',
        'component' => FormulaComponentEnum::class,
        'amount_type' => FormulaAmountType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $amountType = collect(FormulaAmountType::getValues())->first(fn($type) => $type === $model->amount) ?? FormulaAmountType::NUMBER;
            $model->amount_type = $amountType;
        });
    }

    public function formulaComponents(): HasMany
    {
        return $this->hasMany(FormulaComponent::class);
    }

    // recursive
    // loads only 1st level children
    public function mainChild()
    {
        return $this->hasMany($this, 'parent_id', 'id')->with('formulaComponents');
    }

    // recursive, loads all children
    public function child()
    {
        return $this->mainChild()->with('child.formulaComponents');
    }

    // load 1st level parent
    public function mainParent()
    {
        return $this->belongsTo($this, 'parent_id', 'id');
    }

    // recursive load all parents.
    public function parent()
    {
        return $this->mainParent()->with('parent');
    }
}

// formulas[0][component]:attendance_daily
// formulas[0][value]:present
// formulas[0][amount]:100000
// formulas[1][component]:branch
// formulas[1][value]:1,2
// formulas[1][child][0][component]:gender
// formulas[1][child][0][value]:male
// formulas[1][child][0][amount]:10000
// formulas[1][child][1][component]:gender
// formulas[1][child][1][value]:female
// formulas[1][child][1][amount]:8000
// formulas[1][child][1][child][0][component]:marital_status
// formulas[1][child][1][child][0][value]:single,divorced
// formulas[1][child][1][child][0][amount]:5000
// formulas[1][child][1][child][1][component]:else
// formulas[1][child][1][child][1][value]:else
// formulas[1][child][1][child][1][amount]:0
// formulas[2][component]:else
// formulas[2][value]:else
// formulas[2][amount]:0
