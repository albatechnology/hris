<?php

namespace App\Models;

use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends RequestedBaseModel implements TenantedInterface
{
    use BelongsToUser, TenantedThroughUser;

    protected $fillable = [
        'overtime_id',
        'user_id',
        'schedule_id',
        'shift_id',
        // 'type',
        'date',
        'is_after_shift',
        'duration',
        'real_duration',
        // 'start_at',
        // 'end_at',
        'note',
    ];

    protected $casts = [
        'is_after_shift' => 'boolean',
        // 'type' => OvertimeRequestType::class,
        // 'approval_status' => ApprovalStatus::class,
        // 'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $model) {
            [$hour, $minute] = explode(':', $model->real_duration);
            if (config('app.name') == 'LUMORA' && (int)$hour >= 9) {
                $model->real_duration = '09:00:00';
            } else {
                $model->real_duration = date('H:i:s', strtotime($model->real_duration));
            }

            $model->duration = date('H:i:s', strtotime($model->duration));
        });

        // static::creating(function (self $model) {
        //     $model->approved_by = $model->user->approval?->id ?? null;
        // });
    }

    protected $appends = ['duration_text', 'approval_status'];
    public function getDurationTextAttribute()
    {
        // $startAt = new \DateTime($this->start_at);
        // $endAt = new \DateTime($this->end_at);
        // $interval = $startAt->diff($endAt);

        // $result = '';
        // if ((int)$interval->format('%h')) {
        //     $hour = (int)$interval->format('%h');
        //     $hour += (int)$interval->format('%d') * 24;

        //     $result .= $hour . 'h ';
        // }
        // if ((int)$interval->format('%i')) {
        //     $result .= (int)$interval->format('%i') . 'm';
        // }

        // return trim($result);

        list($hours, $minutes, $seconds) = explode(':', $this->real_duration);

        $result = '';
        if ((int)$hours > 0) {
            $result .= (int)$hours . 'h ';
        }
        if ((int)$minutes > 0) {
            $result .= (int)$minutes . 'm ';
        }
        if ((int)$seconds > 0) {
            $result .= (int)$seconds . 's';
        }

        return trim($result);
    }

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeWhereDateBetween($query, string $dateFrom, string $dateTo)
    {
        $query->where(fn($q) => $q->whereDate('date', '>=', $dateFrom)->whereDate('date', '<=', $dateTo));
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }
}
