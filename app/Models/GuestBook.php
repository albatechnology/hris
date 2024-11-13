<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuestBook extends BaseModel implements HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'client_id',
        'is_check_out',
        'name',
        'address',
        'location_destination',
        'room',
        'person_destination',
        'vehicle_number',
        'description',
        'check_out_at',
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
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) {
            return $query;
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereHas('client', fn($q) => $q->whereIn('company_id', $companyIds));
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
