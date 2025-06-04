<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuestBook extends BaseModel implements HasMedia, TenantedInterface
{
    use BelongsToUser, InteractsWithMedia, BelongsToBranch, CustomSoftDeletes;

    protected $fillable = [
        'user_id',
        // 'client_id',
        'branch_id',
        'is_check_out',
        'name',
        'address',
        'location_destination',
        'room',
        'person_destination',
        'vehicle_number',
        'description',
        'check_out_at',
        'check_out_by',
    ];

    protected $casts = [
        'is_check_out' => 'boolean',
    ];

    protected $appends = [
        'check_in_files',
        'check_out_files'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->user_id = auth()->id();
        });
    }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('branch', fn($q) => $q->tenanted());
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function getCheckInFilesAttribute()
    {
        $files = $this->getMedia(\App\Enums\MediaCollection::GUEST_BOOK_CHECK_IN->value);

        $data = [];
        foreach ($files as $file) {
            $data[] = $file->getUrl();
        }

        return $data;
    }

    public function getCheckOutFilesAttribute()
    {
        $files = $this->getMedia(\App\Enums\MediaCollection::GUEST_BOOK_CHECK_OUT->value);

        $data = [];
        foreach ($files as $file) {
            $data[] = $file->getUrl();
        }

        return $data;
    }

    public function scopeCreatedAtStart(Builder $query, $date)
    {
        $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($date)));
    }

    public function scopeCreatedAtEnd(Builder $query, $date)
    {
        $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($date)));
    }

    // public function client(): BelongsTo
    // {
    //     return $this->belongsTo(Client::class);
    // }

    public function checkOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_out_by');
    }
}
