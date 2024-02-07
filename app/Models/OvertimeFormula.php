<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeFormula extends BaseModel
{
    protected $fillable = [
        'overtime_id',
        'component',
        'parent_id',
        'amount',
    ];

    protected $casts = [
        'overtime_id' => 'integer',
        'component' => 'boolean',
        'parent_id' => 'integer',
        'amount' => 'float',
    ];

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }
}
