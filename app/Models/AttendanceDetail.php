<?php

namespace App\Models;

use App\Enums\AttendanceType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDetail extends BaseModel
{
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

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
