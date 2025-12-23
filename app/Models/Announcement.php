<?php

namespace App\Models;

use App\Enums\JobLevel;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CreatedUpdatedInfo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Announcement extends BaseModel implements TenantedInterface, HasMedia
{
    use CompanyTenanted, InteractsWithMedia, CreatedUpdatedInfo;

    protected $fillable = [
        'company_id',
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
