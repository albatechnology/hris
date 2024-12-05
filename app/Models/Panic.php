<?php

namespace App\Models;

use App\Enums\PanicStatus;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panic extends BaseModel implements TenantedInterface
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
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
