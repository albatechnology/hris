<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends BaseModel
{
    protected $fillable = ['name'];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
