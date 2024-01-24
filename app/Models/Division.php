<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends BaseModel
{
    protected $fillable = [
        'company_id',
        'name',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
