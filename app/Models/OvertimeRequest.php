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
        'user_id',
        'schedule_id',
        'shift_id',
        // 'type',
        'date',
        'is_after_shift',
        'duration',
        // 'start_at',
        // 'end_at',
        'note',
        // 'approval_status',
        // 'approved_by',
        // 'approved_at',
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

        list($hours, $minutes, $seconds) = explode(':', $this->duration);

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

    // public function scopeTenanted(Builder $query): Builder
    // {
    //     /** @var User $user */
    //     $user = auth('sanctum')->user();
    //     if ($user->is_super_admin) {
    //         return $query;
    //     }
    //     if ($user->is_administrator) {
    //         return $query->whereHas('user', fn($q) => $q->whereIn('type', [UserType::ADMINISTRATOR, UserType::USER])->where('group_id', $user->group_id));
    //     }

    //     return $query->where('user_id', $user->id);
    // }

    // public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    // {
    //     $query->tenanted()->where('id', $id);
    //     if ($fail) {
    //         return $query->firstOrFail();
    //     }

    //     return $query->first();
    // }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }
}
