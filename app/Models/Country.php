<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name'
    ];

    public function countrySettings(): HasMany
    {
        return $this->hasMany(CountrySetting::class);
    }
}
