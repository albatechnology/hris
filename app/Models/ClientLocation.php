<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Builder;

class ClientLocation extends BaseModel implements HasMedia, TenantedInterface
{
    use CustomSoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'client_id',
        'name',
        'lat',
        'lng',
        'address',
        'description',
    ];

    protected $appends = ['qr_code'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::ulid();
        });
    }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        return $query->whereHas('client', fn($q) => $q->tenanted());
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getQrCodeAttribute()
    {
        $file = $this->getFirstMedia(\App\Enums\MediaCollection::QR_CODE->value);
        if ($file) {
            $url = $file->getUrl();
            $preview = null;
        } else {
            $url = null;
            $preview = null;
        }

        return [
            'url' => $url,
            'preview' => $preview
        ];
    }
}
