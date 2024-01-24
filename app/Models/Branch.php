<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends BaseModel
{
    protected $fillable = [
        'company_id',
        'name',
        'country',
        'province',
        'city',
        'zip_code',
        'lat',
        'lng',
        'address',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
