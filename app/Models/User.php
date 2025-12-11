<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ScheduleType;
use App\Enums\UserType;
use App\Interfaces\TenantedInterface;
use App\Services\UserService;
use App\Traits\Models\BelongsToBranch;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Carbon\Carbon;
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
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements TenantedInterface, HasMedia, MustVerifyEmail
{
    use HasApiTokens, HasRoles, Notifiable, InteractsWithMedia, CustomSoftDeletes, CreatedUpdatedInfo, BelongsToBranch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'company_id',
        'branch_id',
        'level_id',
        'live_attendance_id',
        'name',
        'last_name',
        'email',
        'work_email',
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
        'rehire_date',
        // 'total_remaining_timeoff',
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

    protected $appends = [
        'image',
    ];

    public function scopeTenanted(Builder $query, ?bool $isDescendant = false): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;

        // if ($user->is_administrator) {
        //     return $query->whereIn('users.type', [UserType::ADMINISTRATOR, UserType::ADMIN, UserType::USER])
        //         ->whereHas('companies', fn($q) => $q->whereHas('company', fn($q) => $q->where('companies.group_id', $user->group_id)));
        // }

        if (config('app.name') === 'SYNTEGRA') {
            if ($user->is_admin) return $query->where('company_id', $user->company_id);

            if ($user->is_supervisor) {
                $branch = Branch::select('parent_id', 'is_main')->findOrFail($user->branch_id);

                if ($branch->is_main) {
                    return $query->where('company_id', $user->company_id);
                }

                // if branch parent_id is null, its mean branch, otherwise its client
                if (!$branch->parent_id) {
                    return $query->where(fn($q) => $q->where('branch_id', $user->branch_id)->orWhereHas('branch', fn($q) => $q->where('parent_id', $user->branch_id)));
                }

                return $query->where('branch_id', $user->branch_id);
            }

            return $query->where('branch_id', $user->branch_id);
        }

        if (config('app.name') == 'SUNSHINE') {
            $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
            $query->whereIn('company_id', $companyIds);
        } else {
            $query->where('group_id', $user->group_id);
        }

        if ($user->is_admin) {
            return $query;
            // return $query->where('group_id', $user->group_id);
        }

        // if ($isDescendant) {
        //     if ($user->is_admin) {
        //         $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        //         $query->whereHas('companies', fn($q) => $q->where('company_id', $companyIds));
        //     } else {
        //         $query->whereHas('supervisors', fn($q) => $q->where('supervisor_id', $user->id));
        //     }
        //     return $query->where('users.id', '!=', $user->id);
        // }

        if ($user->hasPermissionTo(Permission::select('id')->firstWhere('name', 'can_read_all_users'))) {
            return $query;
        } else if ($isDescendant) {
            $query->whereHas('supervisors', fn($q) => $q->where('supervisor_id', $user->id));
            return $query->where('users.id', '!=', $user->id);
        } else if (UserService::hasDescendants($user)) {
            return $query->where(function ($q) use ($user) {
                $q->whereHas('supervisors', fn($q) => $q->where('supervisor_id', $user->id))->orWhere('id', $user->id);
            });
        }

        return $query->where('id', $user->id);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function scopeActivePatrolBranchId(Builder $query, int $branchId)
    {
        $query->whereHas('schedules', function ($q) {
            $q->where('schedules.type', ScheduleType::PATROL);
            $q->whereDate('schedules.effective_date', '<=', now());
            $q->orderBy('schedules.effective_date', 'desc');
        })->whereHas('detail', function ($q) {
            $q->where('user_details.detected_at', '>=', Carbon::now()->subMinutes(15)->toDateTimeString());
        })->whereHas('patrols.branch', function ($q) use ($branchId) {
            $q->tenanted();
            $q->where('branches.id', $branchId);
        });
    }

    public function scopeHasScheduleId(Builder $query, int $scheduleId)
    {
        $query->whereHas('schedules', fn($q) => $q->where('user_schedules.schedule_id', $scheduleId));
    }

    public function scopeJobLevel(Builder $query, ...$value)
    {
        $query->where(function ($q) use ($value) {
            // $q->whereNull('parent_id');
            $q->orWhereHas('detail', fn($q2) => $q2->whereIn('user_details.job_level', $value));
        });
    }

    public function scopeWhereTypeUnder(Builder $query, UserType $userType)
    {
        if ($userType->is(UserType::ADMINISTRATOR)) {
            return $query->whereIn('type', [UserType::ADMINISTRATOR, UserType::ADMIN, UserType::USER]);
        } elseif ($userType->is(UserType::ADMIN)) {
            return $query->whereIn('type', [UserType::ADMIN, UserType::USER]);
        } elseif ($userType->is(UserType::USER)) {
            return $query->where('type', UserType::USER);
        }

        return $query;
    }

    public function scopeWhereName(Builder $query, string $value)
    {
        $query->where(fn($q) => $q->where('name', 'like', '%' . $value . '%'));
    }

    public function scopeScheduleType(Builder $query)
    {
        return $query;
    }

    public function scopeSelectMinimalist(Builder $query, array $additionalColumns = [])
    {
        $query->select(['id', 'group_id', 'company_id', 'branch_id', 'name', 'email', 'type', 'nik', ...$additionalColumns]);
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i');
    }

    public function scopeWhereLike(Builder $query, $column, $value)
    {
        $query->where($column, 'LIKE', '%' . $value . '%');
    }

    public function scopeOrWhereLike(Builder $query, $column, $value)
    {
        $query->orWhere($column, 'LIKE', '%' . $value . '%');
    }

    public function scopeWhereDateBetween(Builder $query, $column, string $startDate, string $endDate)
    {
        $query->where(fn($q) => $q->whereDate($column, '>=', $startDate)->whereDate($column, '<=', $endDate));
    }

    public function scopeWhereResignDateAfter(Builder $query, string $value)
    {
        $query->where(fn($q) => $q->whereDate('resign_date', '>=', date('Y-m-d', strtotime($value)))->orWhereNull('resign_date'));
    }

    public function scopeWhereResignDateBefore(Builder $query, string $value)
    {
        $query->where(fn($q) => $q->whereDate('resign_date', '<=', date('Y-m-d', strtotime($value)))->orWhereNull('resign_date'));
    }

    public function scopeShowResignUsers(Builder $query, ?bool $value = false)
    {
        $query->where(
            fn($q) => $q->whereNull('resign_date')
                ->when(isset($value) && $value == true, fn($q) => $q->orWhereNotNull('resign_date'))
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => bcrypt($value),
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

    // public function manager(): BelongsTo
    // {
    //     return $this->belongsTo(self::class, 'parent_id');
    // }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
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

    public function resignation(): HasOne
    {
        return $this->hasOne(UserResignation::class)->where('type', \App\Enums\ResignationType::RESIGN)->orderByDesc('id');
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

    public function reimbursementCategories(): BelongsToMany
    {
        return $this->belongsToMany(ReimbursementCategory::class, 'user_reimbursement_categories')->withPivot('limit_amount');
    }

    public function overtimes(): BelongsToMany
    {
        return $this->belongsToMany(Overtime::class, 'user_overtimes')->withPivot(['is_default']);
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

    // public function activeSchedule(): HasOne
    // {
    //     return $this->hasOne(UserSchedule::class)->whereHas('schedule', function ($q) {
    //         $q->whereDate('effective_date', '<=', now());
    //         $q->orderBy('effective_date', 'desc');
    //     });
    // }

    public function liveAttendance(): BelongsTo
    {
        return $this->belongsTo(LiveAttendance::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'user_events', 'user_id', 'event_id');
    }

    public function taskRequests(): HasMany
    {
        return $this->hasMany(TaskRequest::class);
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

    // public function timeoffRegulationMonths(): HasMany
    // {
    //     return $this->hasMany(UserTimeoffRegulationMonth::class);
    // }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function runPayrolls(): HasMany
    {
        return $this->hasMany(RunPayroll::class);
    }

    public function runThrs(): HasMany
    {
        return $this->hasMany(RunThr::class);
    }

    public function runPayrollUser(): HasMany
    {
        return $this->hasMany(RunPayrollUser::class);
    }

    public function runThrUser(): HasMany
    {
        return $this->hasMany(RunThrUser::class);
    }

    public function patrols(): BelongsToMany
    {
        return $this->belongsToMany(Patrol::class, UserPatrol::class);
    }

    public function userPatrols(): HasMany
    {
        return $this->hasMany(UserPatrol::class);
    }

    public function patrolBatches(): HasMany
    {
        return $this->hasMany(UserPatrolBatch::class);
    }

    // public function userPatrolSchedules(): BelongsToMany
    // {
    //     return $this->belongsToMany(UserPatrolSchedule::class, UserPatrol::class, 'user_id', 'id');
    // }

    public function userPatrolLocations(): HasMany
    {
        return $this->hasMany(UserPatrolLocation::class);
    }

    public function userPatrolTasks(): HasMany
    {
        return $this->hasMany(UserPatrolTask::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function panics(): HasMany
    {
        return $this->hasMany(Panic::class);
    }

    public function timeoffQuotas(): HasMany
    {
        return $this->hasMany(TimeoffQuota::class);
    }

    public function timeoffs(): HasMany
    {
        return $this->hasMany(Timeoff::class);
    }

    public function requestSchedules(): HasMany
    {
        return $this->hasMany(RequestSchedule::class);
    }

    public function reprimands(): HasMany
    {
        return $this->hasMany(Reprimand::class);
    }

    public function userBpjs(): HasOne
    {
        return $this->hasOne(UserBpjs::class);
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->type->is(UserType::SUPER_ADMIN);
    }

    public function getIsAdministratorAttribute(): bool
    {
        return $this->type->is(UserType::ADMINISTRATOR);
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->type->is(UserType::ADMIN);
    }

    public function getIsSupervisorAttribute(): bool
    {
        return $this->type->in([UserType::SUPERVISOR, UserType::MANAGER]);
    }

    public function getIsUserAttribute(): bool
    {
        return $this->type->is(UserType::USER);
    }

    public function deleteRoles()
    {
        DB::table('model_has_roles')->where('model_type', get_class($this))->where('model_id', $this->id)->delete();
    }

    public function supervisors()
    {
        return $this->hasMany(UserSupervisor::class)->orderBy('order');
    }

    public function additional_supervisors()
    {
        return $this->hasMany(UserSupervisor::class)->where('is_additional_supervisor', true)->orderBy('order');
    }

    public function getTotalWorkingMonth(?string $cutoffDate = null, bool $returnAllData = false): int|array
    {
        if ($cutoffDate == null) {

            // $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', $this->company_id)->first(['cut_off_date']);
            // $cutoffDate = $timeoffRegulation->cut_off_date;

            $payrollSetting = PayrollSetting::where('company_id', $this->company_id)->first(['id', 'cut_off_date']);
            $cutoffDate = $payrollSetting->cut_off_date;
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

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
