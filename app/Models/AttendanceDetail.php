<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Builder;

class AttendanceDetail extends RequestedBaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'attendance_id',
        'is_clock_in',
        'time',
        'type',
        // 'approval_status',
        // 'approved_by',
        // 'approved_at',
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

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model) {
            if ($model->type->is(AttendanceType::MANUAL)) {
                $model->approved_by = $model->attendance->user->approval?->id ?? null;
            } elseif ($model->type->is(AttendanceType::AUTOMATIC)) {
                $model->approval_status = ApprovalStatus::APPROVED;
            }
        });
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $q)
    {
        $q->where(
            fn($q) => $q->where('type', AttendanceType::AUTOMATIC)->orWhere('approval_status', ApprovalStatus::APPROVED)
            // ->orWhere(fn($q) => $q->whereNot('type', AttendanceType::MANUAL)->where('approval_status', ApprovalStatus::APPROVED))
        );
    }

    public function getImageAttribute()
    {
        $file = $this->getFirstMedia(\App\Enums\MediaCollection::ATTENDANCE->value);
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
}
