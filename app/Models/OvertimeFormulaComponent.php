<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeFormulaComponent extends BaseModel
{
    protected $fillable = [
        'overtime_formula_id',
        'value',
    ];

    protected $casts = [
        'overtime_formula_id' => 'integer',
        'value' => 'string',
    ];

    public function overtimeFormula(): BelongsTo
    {
        return $this->belongsTo(OvertimeFormula::class);
    }
}
