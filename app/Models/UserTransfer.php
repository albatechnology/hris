<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\EmploymentStatus;
use App\Enums\TransferType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserTransfer extends BaseModel implements TenantedInterface, HasMedia
{
    use BelongsToUser, InteractsWithMedia;

    const FROM_COLUMNS = [
        'employment_status',
        'approval_id',
        'parent_id',
        'branches',
        'positions',
    ];

    protected $fillable = [
        'user_id',
        'from',
        'type',
        'effective_date',
        'employment_status',
        'approval_id',
        'parent_id',
        'reason',
        'is_notify_manager',
        'is_notify_user',
        'approval_status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'from' => 'array',
        'type' => TransferType::class,
        'employment_status' => EmploymentStatus::class,
        'is_notify_manager' => 'boolean',
        'is_notify_user' => 'boolean',
        'approval_status' => ApprovalStatus::class,
    ];

    protected $appends = ['to', 'file'];

    protected static function booted(): void
    {
        static::updating(function (self $model) {
            if ($model->isDirty('approval_status') && !$model->approval_status->is(ApprovalStatus::PENDING)) {
                /** @var User $user */
                $user = $model->user;

                $data = [];
                foreach (self::FROM_COLUMNS as $column) {
                    $data[$column] = match ($column) {
                        'employment_status' => $user->detail?->employment_status?->value ?? '',
                        'approval_id' => $user->approval?->name ?? '',
                        'parent_id' => $user->manager?->name ?? '',
                        'branches' => $user->branches()->select('branch_id')->with('branch', fn ($q) => $q->select('id', 'name'))->get()->map(function ($data) {
                            return $data->branch?->name ?? '';
                        }) ?? [],
                        'positions' => $user->positions()->with([
                            'position' => fn ($q) => $q->select('id', 'name'),
                            'department' => fn ($q) => $q->select('id', 'name'),
                        ])->get()->map(function ($data) {
                            return [
                                'department' => $data->department?->name ?? '',
                                'position' => $data->position?->name ?? '',
                            ];
                        }) ?? [],
                        default => null,
                    };
                }
                $model->from = $data;
            }
        });

        static::updated(function (self $model) {
            if ($model->isDirty('approval_status') && $model->approval_status->is(ApprovalStatus::APPROVED)) {
                /** @var User $user */
                $user = $model->user;
                foreach (self::FROM_COLUMNS as $column) {
                    match ($column) {
                        'employment_status' => $user->detail->update([$column => $model->{$column}]),
                        'approval_id',
                        'parent_id' => $user->update([$column => $model->{$column}]),
                        default => null,
                    };
                }

                $branches = $model->branches()->select('branch_id')->get();
                if ($branches->count() > 0) {
                    $user->branch_id = $branches[0]->branch_id;
                    $user->saveQuietly();

                    $user->branches()->delete();
                    $user->branches()->createMany(
                        $branches->pluck('branch_id')->unique()->values()->map(fn ($branchId) => ['branch_id' => $branchId])->toArray()
                    );
                }

                $positions = $model->positions()->select('department_id', 'position_id')->get();
                if ($positions->count() > 0) {
                    $user->positions()->delete();
                    $user->positions()->createMany($positions->toArray());
                }
            }
        });
    }

    protected function from(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->approval_status->is(ApprovalStatus::PENDING) && !is_null($value)) {
                    return json_decode($value, true);
                }

                /** @var User $user */
                $user = $this->user;
                $data = [];
                foreach (self::FROM_COLUMNS as $column) {
                    $data[$column] = match ($column) {
                        'employment_status' => $user->detail?->employment_status?->value ?? '',
                        'approval_id' => $user->approval?->name ?? '',
                        'parent_id' => $user->manager?->name ?? '',
                        'branches' => $user->branches()->select('branch_id')->with('branch', fn ($q) => $q->select('id', 'name'))->get()->map(function ($data) {
                            return $data->branch?->name ?? '';
                        }) ?? [],
                        'positions' => $user->positions()->with([
                            'position' => fn ($q) => $q->select('id', 'name'),
                            'department' => fn ($q) => $q->select('id', 'name'),
                        ])->get()->map(function ($data) {
                            return [
                                'department' => $data->department?->name ?? '',
                                'position' => $data->position?->name ?? '',
                            ];
                        }) ?? [],
                        default => null,
                    };
                }
                return $data;
            },
        );
    }

    protected function to(): Attribute
    {
        return Attribute::make(
            get: function () {
                $data = [];
                foreach (self::FROM_COLUMNS as $column) {
                    $data[$column] = match ($column) {
                        'employment_status' => $this->{$column},
                        'approval_id' => $this->approval?->name ?? null,
                        'parent_id' => $this->manager?->name ?? null,
                        'branches' => $this->branches()->select('branch_id')->with('branch', fn ($q) => $q->select('id', 'name'))->get()->map(function ($data) {
                            return $data->branch?->name ?? '';
                        }),
                        'positions' => $this->positions()->with([
                            'position' => fn ($q) => $q->select('id', 'name'),
                            'department' => fn ($q) => $q->select('id', 'name'),
                        ])->get()->map(function ($data) {
                            return [
                                'department' => $data->department?->name ?? '',
                                'position' => $data->position?->name ?? '',
                            ];
                        }),
                        default => null,
                    };
                }
                return $data;
            },
        );
    }

    protected function file(): Attribute
    {
        return Attribute::make(
            get: function () {
                $file = $this->getFirstMedia(\App\Enums\MediaCollection::USER_TRANSFER->value);
                return [
                    'url' => $file ? $file->getUrl() : null,
                ];
            },
        );
    }

    public function scopeTenanted(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        if ($user->is_super_admin) {
            return $query;
        }

        if ($user->is_administrator) {
            $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
            return $query->whereHas('user', fn ($q) => $q->whereIn('company_id', $companyIds));
        }

        $query->orWhere('approved_by', $user->id)->orWhere('user_id', $user->id)->orWhere('approval_id', $user->id);

        if ($user->descendants()->exists()) {
            return $query->orWhereHas('user', fn ($q) => $q->whereDescendantOf($user));
        }

        return $query->orWhereHas('user', fn ($q) => $q->where('approval_id', $user->id));
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
        return $this->hasMany(UserTransferBranch::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(UserTransferPosition::class);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
