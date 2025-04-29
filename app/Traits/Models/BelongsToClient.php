<?php

namespace App\Traits\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToClient
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeWhenClient(Builder $query, ?int $clientId = null): Builder
    {
        return $query->when($clientId, fn($q) => $q->where('client_id', $clientId));
    }
}
