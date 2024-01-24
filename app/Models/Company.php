<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends BaseModel
{
    protected $fillable = [
        'group_id',
        'name',
        'country',
        'province',
        'city',
        'zip_code',
        'lat',
        'lng',
        'address',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
