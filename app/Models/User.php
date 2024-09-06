<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\Gender;
use App\Enums\UserType;
use App\Interfaces\TenantedInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements TenantedInterface, HasMedia, MustVerifyEmail
{
    use HasApiTokens, HasRoles, Notifiable, InteractsWithMedia, NodeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'company_id',
        'branch_id',
        'live_attendance_id',
        'overtime_id',
        'approval_id',
        'parent_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'fcm_token',
        'type',
        'nik',
        'phone',
        'gender',
        'join_date',
        'sign_date',
        'end_contract_date',
        'resign_date',
        'total_timeoff',
        'total_remaining_timeoff',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'type' => UserType::class,
        'gender' => Gender::class,
    ];

    protected $appends = ['image'];

    public function scopeTenanted(Builder $query, bool $isDescendant = false): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }
        if ($user->is_administrator) {
            return $query->whereIn('users.type', [UserType::ADMINISTRATOR, UserType::USER])
                ->whereHas('companies', fn ($q) => $q->whereHas('company', fn ($q) => $q->where('companies.group_id', $user->group_id)));
            // ->whereHas('company', fn ($q) => $q->where('group_id', $user->group_id));
        }

        if ($isDescendant) {
            if ($user->descendants()->exists()) {
                return $query->whereDescendantOf($user);
            }

            return $query->where('id', $user->id);
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereIn('users.company_id', $companyIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function scopeHasScheduleId(Builder $query, int $scheduleId)
    {
        $query->whereHas('schedules', fn ($q) => $q->where('user_schedules.schedule_id', $scheduleId));
    }

    // public function getParentIdName()
    // {
    //     return 'parent';
    // }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i');
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => bcrypt($value ?? 'alba#123'),
        );
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    public function payrollInfo(): HasOne
    {
        return $this->hasOne(UserPayrollInfo::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(UserExperience::class);
    }

    public function oneTimePayrollComponents(): HasMany
    {
        return $this->hasMany(OneTimePayrollComponent::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(UserEducation::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(UserContact::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(UserCompany::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(UserBranch::class);
    }

    public function requestChangeDatas(): HasMany
    {
        return $this->hasMany(RequestChangeData::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(UserDepartmentPosition::class);
    }

    public function timeoffPolicies(): BelongsToMany
    {
        return $this->belongsToMany(TimeoffPolicy::class, 'user_timeoff_policies');
    }

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'user_schedules', 'user_id', 'schedule_id');
    }

    public function liveAttendance(): BelongsTo
    {
        return $this->belongsTo(LiveAttendance::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'user_events', 'user_id', 'event_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'user_tasks');
    }

    // public function customFields(): BelongsToMany
    // {
    //     return $this->belongsToMany(CustomField::class, 'user_custom_fields', 'user_id', 'custom_field_id');
    // }

    public function customFields(): HasMany
    {
        return $this->hasMany(UserCustomField::class);
    }

    public function timeoffRegulationMonths(): HasMany
    {
        return $this->hasMany(UserTimeoffRegulationMonth::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function runPayrolls(): HasMany
    {
        return $this->hasMany(RunPayroll::class);
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->type->is(UserType::SUPER_ADMIN);
    }

    public function getIsAdministratorAttribute(): bool
    {
        return $this->type->is(UserType::ADMINISTRATOR);
    }

    public function getIsUserAttribute(): bool
    {
        return $this->type->is(UserType::USER);
    }

    public function deleteRoles()
    {
        DB::table('model_has_roles')->where('model_type', get_class($this))->where('model_id', $this->id)->delete();
    }

    public function getTotalWorkingMonth(?string $cutoffDate = null, bool $returnAllData = false): int|array
    {
        if ($cutoffDate == null) {
            $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', $this->company_id)->first(['cut_off_date']);

            $cutoffDate = $timeoffRegulation->cut_off_date;
        }

        $joinDate = date_create($this->join_date);
        $cutoffDate = date_create(date('Y-m-' . $cutoffDate));
        $diff = date_diff($joinDate, $cutoffDate);

        $totalYear = 0;
        $totalMonth = 0;
        if ($joinDate < $cutoffDate) {
            $totalYear = (int)$diff->format('%y');

            $totalMonth = $totalYear > 0 ? ($totalYear * 12) : 0;
            $totalMonth += (int)$diff->format('%m');
        }

        if ($returnAllData) {
            return [
                'join_date' => $joinDate,
                'cut_off_date' => $cutoffDate,
                'diff' => $diff,
                'total_year' => $totalYear,
                'total_month' => $totalMonth
            ];
        }
        return $totalMonth;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(\App\Enums\MediaCollection::USER->value)
            ->onlyKeepLatest(1)
            ->registerMediaConversions(function (\Spatie\MediaLibrary\MediaCollections\Models\Media $media) {
                $this->addMediaConversion('preview')
                    ->fit(\Spatie\Image\Enums\Fit::Contain, 250, 250)
                    ->nonQueued();
            });
    }

    public function getImageAttribute()
    {
        $file = $this->getFirstMedia(\App\Enums\MediaCollection::USER->value);
        if ($file) {
            $url = $file->getUrl();
            $preview = $file->getUrl('preview');
        } else {
            $url = asset('img/user-icon.png');
            $preview = asset('img/user-icon.png');
        }

        return [
            'url' => $url,
            'preview' => $preview
        ];
    }
}
