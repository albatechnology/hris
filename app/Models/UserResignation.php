<?php

namespace App\Models;

use App\Enums\CountrySettingKey;
use App\Enums\EmploymentStatus;
use App\Enums\ResignationReason;
use App\Enums\ResignationType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use App\Traits\Models\TenantedThroughUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserResignation extends BaseModel implements TenantedInterface
{
    use CustomSoftDeletes, CreatedUpdatedInfo, TenantedThroughUser;

    protected $fillable = [
        'user_id',
        'type',
        'resignation_date',

        // FOR RESIGN
        'reason',
        'merit_pay_amount',
        'severance_amount',
        'compensation_amount',
        'total_day_timeoff_compensation',
        'timeoff_amount_per_day',
        'total_timeoff_compensation',

        // FOR REHIRE
        'nik',
        'branch_id',
        'schedule_id',
        'department_id',
        'position_id',
        'employment_status',
        'basic_salary',

        'description',
    ];

    protected $casts = [
        'type' => ResignationType::class,
        'reason' => ResignationReason::class,
        'employment_status' => EmploymentStatus::class,
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::created(function (self $model) {
            /** @var User $user */
            $user = $model->user;

            if ($model->type->is(ResignationType::REHIRE)) {
                UserSchedule::where('user_id', $model->user_id)->delete();
                $user->positions()->delete();
                $user->branches()->delete();
                $user->companies()->delete();

                $user->update([
                    'nik' => $model->nik,
                    'branch_id' => $model->branch_id,
                    'resign_date' => null,
                    'rehire_date' => $model->resignation_date,
                ]);

                $user->branches()->create([
                    'branch_id' => $model->branch_id
                ]);

                $user->companies()->create([
                    'company_id' => $model->branch->company->id
                ]);

                $user->payrollInfo()->update([
                    'basic_salary' => $model->basic_salary
                ]);

                $upahBpjsKesehatan = CountrySetting::where('country_id', $model->branch->company->country_id)->where('key', CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY)->value('value') ?? 12000000;
                if ($upahBpjsKesehatan && $model->basic_salary < $upahBpjsKesehatan) {
                    $upahBpjsKesehatan = $model->basic_salary;
                }

                $upahBpjsKetenagakerjaan = CountrySetting::where('country_id', $model->branch->company->country_id)->where('key', CountrySettingKey::JP_MAXIMUM_SALARY)->value('value') ?? 10042300;
                if ($upahBpjsKetenagakerjaan && $model->basic_salary < $upahBpjsKetenagakerjaan) {
                    $upahBpjsKetenagakerjaan = $model->basic_salary;
                }

                $user->userBpjs()->update([
                    'upah_bpjs_kesehatan' => $upahBpjsKesehatan,
                    'upah_bpjs_ketenagakerjaan' => $upahBpjsKetenagakerjaan
                ]);

                $user->detail()->update([
                    'employment_status' => $model->employment_status
                ]);

                UserSchedule::create([
                    'user_id' => $model->user_id,
                    'schedule_id' => $model->schedule_id,
                ]);

                $user->positions()->create([
                    'department_id' => $model->department_id,
                    'position_id' => $model->position_id,
                ]);
            } else {
                $user->update([
                    'resign_date' => $model->resignation_date,
                    'rehire_date' => null,
                ]);
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
