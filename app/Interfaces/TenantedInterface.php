<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface TenantedInterface
{
    public function scopeTenanted(Builder $query): Builder;

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail): Model;
}
