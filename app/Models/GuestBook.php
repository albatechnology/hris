<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuestBook extends BaseModel implements HasMedia, TenantedInterface
{
    use BelongsToUser, TenantedThroughBranch, InteractsWithMedia, CustomSoftDeletes;

    protected $fillable = [
        'user_id',
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

    public function checkOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_out_by');
    }
}
