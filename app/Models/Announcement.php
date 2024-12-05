<?php

namespace App\Models;

use App\Enums\JobLevel;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Announcement extends BaseModel implements TenantedInterface
{
    use BelongsToUser, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'user_id',
        'subject',
        'content',
        'is_send_email',
    ];

    public function branches(): MorphToMany
    {
        return $this->morphedByMany(Branch::class, 'announcementable');
    }

    public function positions(): MorphToMany
    {
        return $this->morphedByMany(Position::class, 'announcementable');
    }

    public function jobLevels(): HasMany
    {
        return $this->hasMany(Announcementable::class)->where('announcementable_type', JobLevel::class);
    }
}
