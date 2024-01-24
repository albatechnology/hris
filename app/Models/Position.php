<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends BaseModel
{
    protected $fillable = [
        'company_id',
        'name',
        'order',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
