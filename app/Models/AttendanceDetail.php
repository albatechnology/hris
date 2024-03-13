<?php

namespace App\Models;

use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttendanceDetail extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'attendance_id',
        'is_clock_in',
        'time',
        'type',
        'is_approved',
        'approved_by',
        'approved_at',
        'lat',
        'lng',
        'note',
    ];

    protected $casts = [
        'is_clock_in' => 'boolean',
        'type' => AttendanceType::class,
    ];

    protected $appends = ['image'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
