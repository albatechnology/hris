<?php

namespace App\Models;

use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClientLocation extends BaseModel implements HasMedia
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
            $model->uuid = Str::uuid();
        });
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
            $preview = $file->getUrl('preview');
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
