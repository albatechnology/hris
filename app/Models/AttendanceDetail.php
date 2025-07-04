<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Builder;

class AttendanceDetail extends RequestedBaseModel implements HasMedia
{
    use CustomSoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'attendance_id',
        'is_clock_in',
        'time',
        'type',
        'lat',
        'lng',
        'note',
    ];

    protected $casts = [
        'is_clock_in' => 'boolean',
        'type' => AttendanceType::class,
    ];

    protected $appends = ['approval_status', 'image'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function scopeApproved(Builder $q)
    {
        $q->where(
            fn($q) => $q->where('type', AttendanceType::AUTOMATIC)
                ->orWhere(fn($q) => $q->where('type', AttendanceType::MANUAL)->whereApprovalStatus(ApprovalStatus::APPROVED))
        );
        // $q->where(
        //     fn($q) => $q->where('type', AttendanceType::AUTOMATIC)->orWhere('approval_status', ApprovalStatus::APPROVED)
        //     // ->orWhere(fn($q) => $q->whereNot('type', AttendanceType::MANUAL)->where('approval_status', ApprovalStatus::APPROVED))
        // );
    }

    public function scopeWhereBranch(Builder $q, int $value)
    {
        $q->whereHas('attendance', fn($q) => $q->whereHas('user', fn($q) => $q->where('branch_id', $value)));
    }
    public function scopeWhereUserName(Builder $q, string $value)
    {
        $q->whereHas('attendance', fn($q) => $q->whereHas('user', fn($q) => $q->whereLike('name', $value)));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(\App\Enums\MediaCollection::ATTENDANCE->value)
            ->onlyKeepLatest(1)
            ->registerMediaConversions(function (\Spatie\MediaLibrary\MediaCollections\Models\Media $media) {
                $this->addMediaConversion('preview')
                    ->fit(\Spatie\Image\Enums\Fit::Contain, 100, 100)
                    ->nonQueued();
            });
    }

    public function getImageAttribute()
    {
        $file = $this->getFirstMedia(\App\Enums\MediaCollection::ATTENDANCE->value);
        return [
            'url' => $file?->getUrl() ?? null,
            'preview' => $file?->getUrl('preview') ?? null
        ];
    }
}
