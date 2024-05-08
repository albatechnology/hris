<?php

namespace App\Traits\Models;

use App\Models\Formula;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait MorphManyFormulas
{
    public function formulas(): MorphMany
    {
        return $this->morphMany(Formula::class, 'formulaable')->whereNull('parent_id');
    }

    public function scopeHasFormulas($query, $value)
    {
        if ($value == true) return $query->has('formulas');

        return $query->doesntHave('formulas');
    }
}
