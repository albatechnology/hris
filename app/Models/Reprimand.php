<?php

namespace App\Models;

use App\Enums\MediaCollection;
use App\Enums\ReprimandMonthType;
use App\Enums\ReprimandType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Reprimand extends BaseModel implements TenantedInterface, HasMedia
{
    use BelongsToUser, CreatedUpdatedInfo, CustomSoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'run_reprimand_id',
        'month_type',
        'type',
        'total_late_minutes',
        'effective_date',
        'end_date',
        'notes',
        'details',
    ];

    protected $casts = [
        'month_type' => ReprimandMonthType::class,
        'type' => ReprimandType::class,
        'details' => 'json',
    ];

    protected $appends = [
        'status',
        // 'file'
    ];

    protected static function booted(): void
    {
        static::created(function (self $model) {
            $model->records()->create([
                'user_id' => $model->user_id,
                'date' => $model->effective_date,
            ]);
        });
    }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) return $query;

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        $query->whereHas('user', fn($q) => $q->whereIn('company_id', $companyIds));

        if ($user->is_admin) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    protected function status(): Attribute
    {
        return new Attribute(
            get: function () {
                return date('Y-m-d') >= $this->effective_date && date('Y-m-d') <= $this->end_date ? 'active' : 'inactive';
            },
        );
    }

    public function getFileAttribute()
    {
        $file = $this->getFirstMedia(MediaCollection::REPRIMAND->value);
        if ($file) {
            $url = $file->getUrl();
            // $preview = $file->getUrl('preview');
        } else {
            $url = null;
            // $preview = asset('img/user-icon.png');
        }

        return [
            'url' => $url,
            // 'preview' => $preview
        ];
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reprimand_watchers');
    }

    public function runReprimand(): BelongsTo
    {
        return $this->belongsTo(RunReprimand::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ReprimandRecord::class);
    }
}
