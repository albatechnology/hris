<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\TransferType;
use App\Interfaces\TenantedInterface;
use App\Traits\Models\BelongsToUser;
use App\Traits\Models\CreatedUpdatedInfo;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserTransfer extends BaseModel implements TenantedInterface, HasMedia
{
    use BelongsToUser, InteractsWithMedia, CreatedUpdatedInfo, CustomSoftDeletes;

    const FROM_COLUMNS = [
        'company_id',
        'branch_id',
        'employment_status',
        'supervisor_id',
        'position_id',
        'department_id',
        // 'approval_id',
        // 'parent_id',
        // 'branches',
        // 'positions',
    ];

    protected $fillable = [
        'user_id',
        'context',
        'type',
        'effective_date',
        'company_id',
        'branch_id',
        'employment_status',
        'supervisor_id',
        'position_id',
        'department_id',
        'reason',
        'executed_at',
        // 'approval_id',
        // 'parent_id',
        // 'is_notify_manager',
        // 'is_notify_user',
        // 'approval_status',
        // 'approved_at',
        // 'approved_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'type' => TransferType::class,
        'employment_status' => EmploymentStatus::class,
        // 'is_notify_manager' => 'boolean',
        // 'is_notify_user' => 'boolean',
        // 'approval_status' => ApprovalStatus::class,
    ];

    protected $appends = ['from', 'to', 'file'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->context = $model->user->load([
                'detail' => fn($q) => $q->select('id', 'user_id', 'employment_status', 'job_level'),
                'company' => fn($q) => $q->select('id', 'name'),
                'branch' => fn($q) => $q->select('id', 'name'),
                'supervisors' => fn($q) => $q->with('supervisor', fn($q) => $q->select('id', 'name')),
                'companies',
                'branches',
                'positions' => fn($q) => $q->with([
                    'department' => fn($q) => $q->select('id', 'name'),
                    'position' => fn($q) => $q->select('id', 'name')
                ]),
            ]);
        });

        // static::updating(function (self $model) {
        //     if ($model->isDirty('approval_status') && !$model->approval_status->is(ApprovalStatus::PENDING)) {
        //         /** @var User $user */
        //         $user = $model->user;

        //         $data = [];
        //         foreach (self::FROM_COLUMNS as $column) {
        //             $data[$column] = match ($column) {
        //                 'employment_status' => $user->detail?->employment_status?->value ?? '',
        //                 'approval_id' => $user->approval?->name ?? '',
        //                 'parent_id' => $user->manager?->name ?? '',
        //                 // 'branches' => $user->branches()->select('branch_id')->with('branch', fn($q) => $q->select('id', 'name'))->get()->map(function ($data) {
        //                 //     return $data->branch?->name ?? '';
        //                 // }) ?? [],
        //                 'positions' => $user->positions()->with([
        //                     'position' => fn($q) => $q->select('id', 'name'),
        //                     'department' => fn($q) => $q->select('id', 'name'),
        //                 ])->get()->map(function ($data) {
        //                     return [
        //                         'department' => $data->department?->name ?? '',
        //                         'position' => $data->position?->name ?? '',
        //                     ];
        //                 }) ?? [],
        //                 default => null,
        //             };
        //         }
        //         $model->from = $data;
        //     }
        // });

        // static::updated(function (self $model) {
        //     if ($model->isDirty('approval_status') && $model->approval_status->is(ApprovalStatus::APPROVED)) {
        //         /** @var User $user */
        //         $user = $model->user;
        //         foreach (self::FROM_COLUMNS as $column) {
        //             match ($column) {
        //                 'employment_status' => $user->detail->update([$column => $model->{$column}]),
        //                 'approval_id',
        //                 'parent_id' => $user->update([$column => $model->{$column}]),
        //                 default => null,
        //             };
        //         }

        //         // $branches = $model->branches()->select('branch_id')->get();
        //         // if ($branches->count() > 0) {
        //         //     $user->branch_id = $branches[0]->branch_id;
        //         //     $user->saveQuietly();

        //         //     $user->branches()->delete();
        //         //     $user->branches()->createMany(
        //         //         $branches->pluck('branch_id')->unique()->values()->map(fn($branchId) => ['branch_id' => $branchId])->toArray()
        //         //     );
        //         // }

        //         // $positions = $model->positions()->select('department_id', 'position_id')->get();
        //         // if ($positions->count() > 0) {
        //         //     $user->positions()->delete();
        //         //     $user->positions()->createMany($positions->toArray());
        //         // }
        //     }
        // });
    }

    protected function from(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $data = [];
                foreach ($this->to as $column => $value) {
                    if ($column == 'company') {
                        $data['company'] = !empty($this->context['company']) ? $this->context['company'] : '';
                    }

                    if ($column == 'branch') {
                        $data['branch'] = !empty($this->context['branch']) ? $this->context['branch'] : '';
                    }

                    if ($column == 'supervisors') {
                        $data['supervisors'] = [];
                        if (!empty($this->context['supervisors'])) {
                            $data['supervisors'] = User::select('id', 'name')->whereIn('id', collect($this->context['supervisors'])->pluck('supervisor_id'))->get();
                        }
                    }

                    if ($column == 'department' && !empty($this->context['positions'])) {
                        $data['department'] = $this->context['positions'][0]['department'] ?? '';
                    }

                    if ($column == 'position' && !empty($this->context['positions'])) {
                        $data['position'] = $this->context['positions'][0]['position'] ?? '';
                    }

                    if ($column == 'employment_status' && !empty($this->context['detail']['employment_status'])) {
                        $data['employment_status'] = $this->context['detail']['employment_status'] ?? '';
                    }
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
                    if ($column == 'company_id' && $this->company_id) {
                        $data['company'] = $this->company()->select('id', 'name')->first();
                    }

                    if ($column == 'branch_id' && $this->branch_id) {
                        $data['branch'] = $this->branch()->select('id', 'name')->first();
                    }

                    if ($column == 'supervisor_id' && $this->supervisor_id) {
                        $data['supervisors'] = [
                            $this->supervisor()->select('id', 'name')->first(),
                        ];
                    }

                    if ($column == 'position_id' && $this->position_id) {
                        $data['position'] = $this->position()->select('id', 'name')->first();
                    }

                    if ($column == 'department_id' && $this->department_id) {
                        $data['department'] = $this->department()->select('id', 'name')->first();
                    }

                    if ($column == 'employment_status' && $this->employment_status) {
                        $data['employment_status'] = $this->employment_status;
                    }
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

        // if ($user->is_administrator) {
        //     $companyIds = $user->companies()->get(['company_id'])?->pluck('company_id') ?? [];
        //     return $query->whereHas('user', fn($q) => $q->whereIn('company_id', $companyIds));
        // }
        if ($user->is_admin) {
            return $query->whereHas('user', fn($q) => $q->tenanted());
        }

        // $query->where('user_id', $user->id);
        // $query->orWhere('approved_by', $user->id)->orWhere('user_id', $user->id)->orWhere('approval_id', $user->id);

        // if ($user->descendants()->exists()) {
        //     return $query->orWhereHas('user', fn ($q) => $q->whereDescendantOf($user));
        // }

        return $query->where(fn($q) => $q->whereHas('user', fn($q) => $q->tenanted())->orWhere('created_by', $user->id));
        // return $query->orWhereHas('user', fn($q) => $q->where('approval_id', $user->id));
    }

    public function scopeFindTenanted(Builder $query, int|string $id, bool $fail = true): self
    {
        $query->tenanted()->where('id', $id);
        if ($fail) {
            return $query->firstOrFail();
        }

        return $query->first();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // public function branches(): HasMany
    // {
    //     return $this->hasMany(UserTransferBranch::class);
    // }

    // public function positions(): HasMany
    // {
    //     return $this->hasMany(UserTransferPosition::class);
    // }

    // public function approval(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approval_id');
    // }

    // public function manager(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'parent_id');
    // }

    // public function approvedBy(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }
}
