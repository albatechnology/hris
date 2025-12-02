<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Incident extends BaseModel implements HasMedia, TenantedInterface
{
    use CustomSoftDeletes, BelongsToUser, InteractsWithMedia, TenantedThroughUser;

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'incident_type_id',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->user_id)) {
                $model->user_id = auth('sanctum')->id();
            }
        });
    }

    public function incidentType(): BelongsTo
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
