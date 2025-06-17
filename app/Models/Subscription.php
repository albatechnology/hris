<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;

class Subscription extends BaseModel
{
    use CustomSoftDeletes, BelongsToUser;

    protected $fillable = [
        'user_id',
        // 'stripe_id',
        // 'stripe_price_id',
        // 'stripe_status',
        // 'stripe_price',
        // 'quantity',
        // 'cancel_at_period_end',
    ];
}
