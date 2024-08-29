<?php

namespace App\Models;

use App\Enums\JobLevel;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Announcement extends BaseModel
{
    use CompanyTenanted;

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

    public function jobLevels()
    {
        return $this->hasMany(Announcementable::class)->where('announcementable_type', JobLevel::class);
    }
}
