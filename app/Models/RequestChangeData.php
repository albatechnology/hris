<?php

namespace App\Models;

use App\Enums\RequestChangeDataType;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class RequestChangeData extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'description',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'type' => RequestChangeDataType::class,
    ];
}
