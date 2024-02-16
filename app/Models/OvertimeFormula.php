<?php

namespace App\Models;

use App\Enums\FormulaComponent;
use App\Traits\TreeStructure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OvertimeFormula extends BaseModel
{
    protected $fillable = [
        'overtime_id',
        'parent_id',
        'component',
        'amount',
    ];

    protected $casts = [
        'overtime_id' => 'integer',
        'parent_id' => 'integer',
        'component' => FormulaComponent::class,
        'amount' => 'float',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function overtimeFormulaComponents(): HasMany
    {
        return $this->hasMany(OvertimeFormulaComponent::class);
    }

    // recursive
    // loads only 1st level children
    public function mainChild()
    {
        return $this->hasMany($this, 'parent_id', 'id')->with('overtimeFormulaComponents');
    }

    // recursive, loads all children
    public function child()
    {
        return $this->mainChild()->with('child.overtimeFormulaComponents');
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
