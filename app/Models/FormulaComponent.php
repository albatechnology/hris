<?php

namespace App\Models;

class FormulaComponent extends BaseModel
{
    protected $fillable = [
        'formula_id',
        'value',
    ];

    protected $casts = [
        'formula_id' => 'integer',
        'value' => 'string',
    ];
}
