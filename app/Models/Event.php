<?php

namespace App\Models;

use App\Enums\EventType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model implements TenantedInterface
{
    use CompanyTenanted, BelongsToBranch;

    protected $fillable = [
        'company_id',
        'branch_id',
        'type',
        'name',
        'start_at',
        'end_at',
        'is_public',
        'is_send_email',
        'description',
    ];

    protected $casts = [
        'type' => EventType::class,
        'is_public' => 'boolean',
        'is_send_email' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->type->is(EventType::NATIONAL_HOLIDAY)) {
                // $model->company_id = null;
                $model->start_at = date('Y-m-d H:i:s', strtotime($model->start_at));
                $model->end_at = $model->start_at;
                $model->is_public = true;
                $model->is_send_email = false;
            }
        });
    }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) return $query;

        if ($user->is_admin) {
            return $query->whereIn('company_id', $user->companies()->get(['company_id'])?->pluck('company_id'));
            // return $query->whereHas('company', fn($q) => $q->where('group_id', $user->group_id));
        }

        // return $query->whereIn('company_id', $user->companies()->get(['company_id'])?->pluck('company_id'));
        if (config('app.name' == 'SYNTEGRA')) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->where('company_id', $user->company_id);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select(['id', 'type', 'name', 'start_at', 'end_at', 'description', ...$additionalColumns]);
    }

    public function userEvents(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_events', 'event_id', 'user_id');
    }

    public function scopeWhereCompanyHoliday(Builder $query)
    {
        return $query->where('type', EventType::COMPANY_HOLIDAY);
    }

    // public function scopeWhereEvent(Builder $query)
    // {
    //     return $query->where('type', EventType::EVENT);
    // }

    public function scopeWhereNationalHoliday(Builder $query)
    {
        return $query->where('type', EventType::NATIONAL_HOLIDAY);
    }

    public function scopeWhereDateBetween(Builder $query, string $startDate, string $endDate)
    {
        $query->whereDate('start_at', '>=', date('Y-m-d', strtotime($startDate)))->whereDate('end_at', '<=', date('Y-m-d', strtotime($endDate)));
    }

    public function scopeWhereYearMonth(Builder $query, string $date)
    {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $query->where(
            fn($q) => $q->where(
                fn($q) => $q->whereYear('start_at', $year)->whereMonth('start_at', $month)
                    ->orWhere(fn($q) => $q->whereYear('end_at', $year)->whereMonth('end_at', $month))
            )
        );
    }
}
