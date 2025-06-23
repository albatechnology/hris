<?php

namespace App\Models;

use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use CreatedUpdatedInfo, CustomSoftDeletes;

    protected $fillable = [
        'subscription_id',
        'payment_at',
        'total_price',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
