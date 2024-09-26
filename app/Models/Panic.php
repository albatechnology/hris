<?php

namespace App\Models;

use App\Enums\PanicStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panic extends BaseModel
{
    protected $fillable = [
        'client_id',
        'user_id',
        'lat',
        'lng',
        'status',
    ];

    protected $casts = [
        'status' => PanicStatus::class,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
