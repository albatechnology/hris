<?php

namespace App\Models;

use App\Enums\PanicStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panic extends BaseModel
{
    protected $fillable = [
        'user_id',
        'lat',
        'lng',
        'status',
    ];

    protected $casts = [
        'status' => PanicStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
