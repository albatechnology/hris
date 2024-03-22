<?php

namespace App\Models;

use App\Enums\CurrencyCode;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes;

    protected $fillable = [
        'group_id',
        'name',
        'country',
        'province',
        'city',
        'zip_code',
        'lat',
        'lng',
        'address',
        'currency_code',
    ];

    protected $casts = [
        'currency_code' => CurrencyCode::class,
    ];

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) {
            return $query;
        }
        if ($user->is_administrator) {
            return $query->where('group_id', $user->group_id);
        }

        $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];

        return $query->whereIn('id', $companyIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timeoffPolicies(): HasMany
    {
        return $this->hasMany(TimeoffPolicy::class);
    }

    public function liveAttendances(): HasMany
    {
        return $this->hasMany(LiveAttendance::class);
    }

    public function payrollComponents(): HasMany
    {
        return $this->hasMany(PayrollComponent::class);
    }

    public function timeoffRegulation(): HasOne
    {
        return $this->hasOne(TimeoffRegulation::class);
    }

    public function payrollSetting(): HasOne
    {
        return $this->hasOne(PayrollSetting::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function supervisorType(): BelongsTo
    {
        return $this->belongsTo(SupervisorType::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    public function createPayrollSetting(): void
    {
        $this->payrollSetting()->create([
            'company_id' => $this->id,
            'cutoff_attendance_start_date' => '02',
            'cutoff_attendance_end_date' => '05',
            'default_employee_tax_setting' => \App\Enums\DefaultEmployeeTaxSetting::GROSS,
            'default_employee_salary_tax_setting' => \App\Enums\DefaultEmployeeTaxSetting::GROSS,
            'default_oas_setting' => \App\Enums\DefaultEmployeeTaxSetting::GROSS,
        ]);
    }
}
