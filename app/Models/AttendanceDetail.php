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
        // 'approval_status' => ApprovalStatus::class,
    ];

    protected $appends = ['approval_status', 'image'];

    // protected static function booted(): void
    // {
    //     parent::booted();

    //     // static::creating(function (self $model) {
    //     //     if ($model->type->is(AttendanceType::MANUAL)) {
    //     //         $model->approved_by = $model->attendance->user->approval?->id ?? null;
    //     //     } elseif ($model->type->is(AttendanceType::AUTOMATIC)) {
    //     //         $model->approval_status = ApprovalStatus::APPROVED;
    //     //     }
    //     // });
    // }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

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
