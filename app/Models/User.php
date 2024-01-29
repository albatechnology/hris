<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\Religion;
use App\Enums\UserType;
use App\Enums\MaritalStatus;
use App\Enums\BloodType;
use App\Interfaces\TenantedInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements TenantedInterface
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'manager_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'type',
        'nik',
        'phone',
        'birth_place',
        'birthdate',
        'marital_status',
        'blood_type',
        'religion',
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
        'blood_type' => BloodType::class,
        'religion' => Religion::class,
        'marital_status' => MaritalStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->type)) $model->type = UserType::USER;
        });

        static::created(function (self $model) {
            $model->detail()->create([]);
            $model->payrollInfo()->create([]);
        });
    }

    public function scopeTenanted(Builder $query): Builder
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        if ($user->is_super_admin) return $query;
        return $query->where('group_id', $user->group_id);
        // return $query->whereHas('company', fn ($q) => $q->where('group_id', $user->group_id));

        // $branchIds = $user->branches()->get(['id'])?->pluck('id') ?? [];
        // return $query->whereIn('id', $branchIds);
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) return $query->firstOrFail();
        return $query->first();
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i');
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string|null $value) => empty($value) ? null : bcrypt($value),
        );
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
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

    public function experiences(): HasMany
    {
        return $this->hasMany(UserExperience::class);
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
}
