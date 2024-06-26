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
            $amountType = collect(FormulaAmountType::getValues())->first(fn ($type) => $type === $model->amount) ?? FormulaAmountType::NUMBER;
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
