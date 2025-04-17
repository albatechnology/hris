<?php

namespace App\Models;

use App\Enums\ReprimandType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reprimand extends BaseModel implements TenantedInterface
{
    use BelongsToUser, CreatedUpdatedInfo, CustomSoftDeletes, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'assign_to',
        'type',
        'effective_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'type' => ReprimandType::class,
    ];

    protected $appends = [
        'status'
    ];

    protected function status(): Attribute
    {
        return new Attribute(
            get: function () {
                return date('Y-m-d') >= $this->effective_date && date('Y-m-d') <= $this->end_date ? 'active' : 'inactive';
            },
        );
    }

    public function assignTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to');
    }
}
