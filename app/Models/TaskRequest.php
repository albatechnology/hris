<?php

namespace App\Models;

use App\Enums\UserType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskRequest extends RequestedBaseModel implements TenantedInterface, HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'task_hour_id',
        'start_at',
        'end_at',
        'note',
        // 'approval_status',
        // 'approved_by',
        // 'approved_at',
    ];

    protected $appends = [
        'approval_status',
        'files'
    ];

    protected static function booted(): void
    {
        parent::booted();
        // static::creating(function (self $model) {
        //     $model->approved_by = $model->user->approval?->id ?? null;
        // });
    }

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }
        if ($user->is_administrator) {
            return $query->whereHas('user', fn($q) => $q->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])->where('group_id', $user->group_id));
        }

        // if ($user->descendants()->exists()) {
        //     return $query->whereHas('user', fn($q) => $q->whereDescendantOf($user));
        // }

        return $query->where('user_id', $user->id);
        // $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        // return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function taskHour(): BelongsTo
    {
        return $this->belongsTo(TaskHour::class);
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

    public function getFilesAttribute()
    {
        $files = $this->getMedia(\App\Enums\MediaCollection::TASK->value);
        $data = [];
        if ($files->count() > 0) {
            foreach ($files as $file) {
                $data[] = $file->getUrl();
            }
        }
        return $data;
    }
}
