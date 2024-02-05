<?php

namespace App\Models;

use App\Enums\TimeoffPolicyType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeoffPolicy extends BaseModel
{
    protected $fillable = [
        'company_id',
        'type',
        'name',
        'code',
        'description',
        'effective_date',
        'is_for_all_user',
        'is_enable_block_leave',
        'is_unlimited_day',
    ];

    protected $casts = [
        'type' => TimeoffPolicyType::class,
        'is_for_all_user' => 'boolean',
        'is_enable_block_leave' => 'boolean',
        'is_unlimited_day' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
