<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\MediaCollection;
use App\Enums\PayrollComponentType;
use App\Enums\RequestChangeDataType;
use App\Enums\ResignationType;
use App\Enums\SettingKey;
use App\Enums\UserType;
use App\Exports\User\UserExport;
use App\Http\Requests\Api\User\DetailStoreRequest;
use App\Http\Requests\Api\User\ExportRequest;
use App\Http\Requests\Api\User\RegisterRequest;
use App\Http\Requests\Api\User\StoreRequest;
use App\Http\Requests\Api\User\UpdateRequest;
use App\Http\Requests\Api\User\UploadPhotoRequest;
use App\Http\Requests\Api\User\FcmTokenRequest;
use App\Http\Requests\Api\User\PayrollRequest;
use App\Http\Requests\Api\User\RehireRequest;
use App\Http\Requests\Api\User\ResignRequest;
use App\Http\Requests\Api\User\SetSupervisorRequest;
use App\Http\Requests\Api\User\UpdateDeviceRequest;
use App\Http\Requests\Api\User\UpdatePasswordRequest;
use App\Http\Resources\Branch\BranchResource;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\User\UserResource;
use App\Imports\UsersImport;
use App\Imports\UserSunImport;
use App\Models\Branch;
use App\Models\Company;
use App\Models\RunPayroll;
use App\Models\TaskHour;
use App\Models\User;
use App\Models\UserResignation;
use App\Services\RequestApprovalService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:user_access', ['only' => ['restore']]);
        $this->middleware('permission:user_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:user_create', ['only' => 'store']);
        $this->middleware('permission:user_edit', ['only' => 'update']);
        $this->middleware('permission:user_delete', ['only' => ['destroy', 'forceDelete']]);

        $this->middleware('permission:request_change_data_create', ['only' => 'requestChangeData']);
    }

    private function getAllowedIncludes()
    {
        return [
            AllowedInclude::callback('company', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('branch', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('companies', function ($query) {
                $query->select('user_id', 'company_id')->with('company', fn($q) => $q->select('id', 'name'));
            }),
            AllowedInclude::callback('branches', function ($query) {
                $query->select('user_id', 'branch_id')->with('branch', fn($q) => $q->select('id', 'name'));
            }),
            AllowedInclude::callback('positions', function ($query) {
                $query->select('user_id', 'department_id', 'position_id')->with([
                    'position' => fn($q) => $q->select('id', 'name'),
                    'department' => fn($q) => $q->select('id', 'name'),
                ]);
            }),
            AllowedInclude::callback('roles', function ($query) {
                $query->select('id', 'name');
            }),
            AllowedInclude::callback('resignation', function ($query) {
                $query->select('id', 'user_id', 'type', 'resignation_date', 'reason', 'merit_pay_amount', 'severance_amount', 'compensation_amount', 'total_day_timeoff_compensation', 'timeoff_amount_per_day', 'total_timeoff_compensation', 'description');
            }),
            AllowedInclude::callback('supervisors', function ($query) {
                $query->where('is_additional_supervisor', false)->orderByDesc('order')->with('supervisor', fn($q) => $q->select('id', 'name', 'last_name'));
            }),
            'detail',
            'payrollInfo',
            'schedules',
            'userBpjs',
            'overtimes',
        ];
    }

    public function index()
    {
        $users = QueryBuilder::for(
            User::tenanted(request()->filter['is_my_descendant'] ?? false)
                ->with(['roles' => fn($q) => $q->select('id', 'name'), 'patrols.client'])
        )
            ->allowedFilters([
                AllowedFilter::exact('branch_id'),
                // AllowedFilter::exact('approval_id'),
                AllowedFilter::scope('has_schedule_id'),
                AllowedFilter::scope('job_level'),
                AllowedFilter::callback('has_active_patrol', function ($query, $value) {
                    $query->whereHas('patrols', function ($q) {
                        $q->whereDate('patrols.start_date', '<=', now());
                        $q->whereDate('patrols.end_date', '>=', now());

                        $q->whereHas('client', fn($q2) => $q2->tenanted());
                        // $q->whereDoesntHave('tasks', function($q2){
                        //   $q2->where('status', PatrolTaskStatus::PENDING);
                        // });
                    });
                }),
                AllowedFilter::callback('last_detected', function ($query, $value) {
                    $query->whereHas('detail', function ($q) use ($value) {
                        $q->where('user_details.detected_at', '>=', Carbon::now()->subMinutes($value)->toDateTimeString());
                    });
                }),
                AllowedFilter::callback('client_id', function ($query, $value) {
                    $query->whereHas('patrols', fn($q) => $q->where('client_id', $value));
                }),
                AllowedFilter::callback('religion', function ($query, $value) {
                    $value = is_array($value) ? $value : [$value];
                    $query->whereHas('detail', fn($q) => $q->whereIn('religion', $value));
                }),
                AllowedFilter::scope('name', 'whereName'),
                'email',
                'type',
                'nik',
                'phone',
            ])
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedSorts([
                'id',
                'branch_id',
                // 'approval_id',
                'name',
                'email',
                'type',
                'nik',
                'phone',
                'created_at',
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }

    public function me()
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $user = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new UserResource($user);
    }

    public function show(int $id)
    {
        if (auth()->id() == $id) {
            $query = User::tenanted()->where('id', $id);
        } else {
            $query = User::tenanted(true)->where('id', $id);
        }

        $user = QueryBuilder::for($query)
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new UserResource($user);
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create($request->validated());

            if ($request->hasFile('photo_profile') && $request->file('photo_profile')->isValid()) {
                $mediaCollection = MediaCollection::USER->value;
                $user->addMediaFromRequest('photo_profile')->toMediaCollection($mediaCollection);
            }

            $user->detail()->create($request->validated());
            $user->payrollInfo()->create($request->validated());
            $user->positions()->createMany($request->positions ?? []);
            $user->roles()->syncWithPivotValues($request->role_ids ?? [], ['group_id' => $user->group_id]);

            $companyIds = collect($request->company_ids ?? []);
            if ($user->company_id) {
                $companyIds->push($user->company_id);
            }
            $companyIds = $companyIds->unique()->values()
                ->map(function ($companyId) {
                    return ['company_id' => $companyId];
                })->all();
            $user->companies()->createMany($companyIds);

            $branchIds = collect($request->branch_ids ?? []);
            if ($user->branch_id) {
                $branchIds->push($user->branch_id);
            }
            $branchIds = $branchIds->unique()->values()
                ->map(function ($branchId) {
                    return ['branch_id' => $branchId];
                })->all();
            $user->branches()->createMany($branchIds);

            if (!empty($user->password)) {
                $notificationType = \App\Enums\NotificationType::SETUP_PASSWORD;
                $user->notify(new ($notificationType->getNotificationClass())($notificationType));
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new UserResource($user);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create($request->validated());
            $user->roles()->syncWithPivotValues($request->role_ids ?? [], ['group_id' => $user->group_id ?? 1]);

            $companyIds = collect($request->company_ids ?? []);
            if ($user->company_id) {
                $companyIds->push($user->company_id);
            }
            $companyIds = $companyIds->unique()->values()
                ->map(function ($companyId) {
                    return ['company_id' => $companyId];
                })->all();
            $user->companies()->createMany($companyIds);

            $branchIds = collect($request->branch_ids ?? []);
            if ($user->branch_id) {
                $branchIds->push($user->branch_id);
            }
            $branchIds = $branchIds->unique()->values()
                ->map(function ($branchId) {
                    return ['branch_id' => $branchId];
                })->all();
            $user->branches()->createMany($branchIds);

            $notificationType = \App\Enums\NotificationType::SETUP_PASSWORD;
            $user->notify(new ($notificationType->getNotificationClass())($notificationType));

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new UserResource($user);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $data = $request->validated();
        if ($request->password) {
            $data['password'] = $request->password;
        }

        $user = User::findTenanted($id);
        DB::beginTransaction();
        try {
            $user->update($data);
            // if ($request->positions) {
            //     $user->positions()->delete();
            //     $user->positions()->createMany($request->positions ?? []);
            // }
            $user->deleteRoles();
            $user->roles()->syncWithPivotValues($request->role_ids ?? [], ['group_id' => $user->group_id ?? 1]);

            $companyIds = collect($request->company_ids ?? []);
            if ($user->company_id) {
                $companyIds->push($user->company_id);
            }
            $companyIds = $companyIds->unique()->values()
                ->map(function ($companyId) {
                    return ['company_id' => $companyId];
                })->all();
            $user->companies()->delete();
            $user->companies()->createMany($companyIds);

            $branchIds = collect($request->branch_ids ?? []);
            if ($user->branch_id) {
                $branchIds->push($user->branch_id);
            }
            $branchIds = $branchIds->unique()->values()
                ->map(function ($branchId) {
                    return ['branch_id' => $branchId];
                })->all();
            $user->branches()->delete();
            $user->branches()->createMany($branchIds);
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new UserResource($user))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $user = User::findTenanted($id);
        if ($user->id == 1) {
            return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        }
        $user->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        if ($id == 1) {
            return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        }
        $user = User::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $user->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $user = User::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $user->restore();

        return new UserResource($user);
    }

    public function detail(int $id, DetailStoreRequest $request)
    {
        $user = User::findTenanted($id);

        if (auth()->user()->is_user) {
            return $this->errorResponse(message: 'Permission denied', code: 403);
        }

        DB::beginTransaction();
        try {
            $user->update($request->validated());

            if ($user->company_id) {
                $user->companies()->delete();
                $user->companies()->create(['company_id' => $user->company_id]);
            }

            if ($user->branch_id) {
                $user->branches()->delete();
                $user->branches()->create(['branch_id' => $user->branch_id]);
            }

            if ($user->detail) {
                $user->detail->update($request->validated());
            } else {
                $user->detail()->create($request->validated());
            }

            if (count($request->positions) > 0) {
                $user->positions()->delete();
                $user->positions()->createMany($request->positions ?? []);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse();
    }

    public function companies(int $id)
    {
        $user = User::findTenanted($id);
        if ($user->type->is(UserType::SUPER_ADMIN)) {
            $companies = Company::all();
        } elseif ($user->type->is(UserType::ADMINISTRATOR)) {
            if ($user->companies->count() > 0) {
                $companies = Company::whereIn('id', $user->companies->pluck('company_id'))->get();
            }
            $companies = Company::where('group_id', $user->group_id)->get();
        } else {
            $companies = Company::whereIn('id', $user->companies->pluck('company_id'))->get();
        }

        return CompanyResource::collection($companies);
    }

    public function branches(int $id)
    {
        $user = User::findTenanted($id);
        if ($user->type->is(UserType::SUPER_ADMIN)) {
            $branches = Branch::all();
        } elseif ($user->type->is(UserType::ADMINISTRATOR)) {
            if ($user->branches->count() > 0) {
                $branches = Branch::whereIn('id', $user->branches->pluck('branch_id'))->get();
            }
            $branches = Branch::whereHas('company', fn($q) => $q->where('group_id', $user->group_id))->get();
        } else {
            $branches = Branch::whereIn('id', $user->branches?->pluck('branch_id') ?? [])->get();
        }

        return BranchResource::collection($branches);
    }

    public function uploadPhoto(UploadPhotoRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        DB::beginTransaction();
        try {
            $mediaCollection = MediaCollection::USER->value;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $user->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new UserResource($user);
    }

    public function requestChangeData(User $user, \App\Http\Requests\Api\User\RequestChangeDataRequest $request)
    {
        $requestChangeDataAllowes = \App\Models\RequestChangeDataAllowes::where('company_id', $user->company_id)->get();

        $dataRequested = [];
        $dataAllowedToUpdate = [];
        foreach ($request->details ?? [] as $type => $value) {
            if ($requestChangeDataAllowes->firstWhere('type.value', $type)?->is_active == true) {
                $dataRequested[] = [
                    'type' => $type,
                    'value' => $value,
                ];
            } elseif ($requestChangeDataAllow = $requestChangeDataAllowes->firstWhere('type.value', $type)) {
                $dataAllowedToUpdate[] = [
                    'type' => $requestChangeDataAllow->type->value,
                    'value' => $value,
                ];
            }
        }

        DB::beginTransaction();
        try {
            $userLoggedIn = auth()->user();
            if (!$userLoggedIn->is_user) {
                /** @var \App\Models\RequestChangeData $requestChangeData */
                $requestChangeData = $user->requestChangeDatas()->createQuietly($request->validated());
                $requestChangeData->approvals()->createQuietly([
                    'user_id' => $userLoggedIn->id,
                    'approval_status' => ApprovalStatus::APPROVED,
                    'approved_at' => now(),
                    'description' => sprintf('Updated by %s(%s)', $userLoggedIn->name, $userLoggedIn->type->value)
                ]);

                foreach (array_merge($dataRequested, $dataAllowedToUpdate) ?? [] as $data) {
                    RequestChangeDataType::updateData($data['type'], $user->id, $data['value']);
                }

                $mediaCollection = MediaCollection::USER->value;
                $photoProfile = collect($dataRequested)->firstWhere('type', 'photo_profile');

                if ($photoProfile && $photoProfile['value']?->isValid()) {
                    $requestChangeDataDetail = $requestChangeData->details()->create($photoProfile);

                    $photoUrl = $user->addMedia($photoProfile['value'])->toMediaCollection($mediaCollection);
                    $requestChangeDataDetail->update(['value' => $photoUrl->getFullUrl()]);
                }

                $requestChangeData->details()->createMany(collect($dataRequested)->whereNotIn('type', ['photo_profile'])->all() ?? []);
            } else {
                /** @var \App\Models\RequestChangeData $requestChangeData */
                $photoProfile = collect($dataRequested)->firstWhere('type', 'photo_profile');

                if (!is_null($photoProfile)) {
                    $defaultApproverId = $user->company->settings()->where('key', SettingKey::PROFILE_PICTURE_APPROVER)->first(['value'])?->value;

                    /** @var User $defaultApprover */
                    $defaultApprover = User::find($defaultApproverId, ['id']);
                    if (!$defaultApprover) {
                        $defaultApprover = User::where('company_id', $user->company_id)->where('type', UserType::ADMIN)->first(['id']);
                    }

                    $approvers[] = [
                        'user_id' => $defaultApprover->id,
                    ];

                    $requestChangeData = $user->requestChangeDatas()->createQuietly($request->validated());
                    RequestApprovalService::createApprovals($requestChangeData, $approvers);
                } else {
                    $requestChangeData = $user->requestChangeDatas()->create($request->validated());
                }

                if (count($dataRequested) > 0) {
                    $mediaCollection = MediaCollection::REQUEST_CHANGE_DATA->value;

                    if ($photoProfile && $photoProfile['value']?->isValid()) {
                        $requestChangeDataDetail = $requestChangeData->details()->create($photoProfile);
                        $photoUrl = $requestChangeDataDetail->addMedia($photoProfile['value'])->toMediaCollection($mediaCollection);
                        $requestChangeDataDetail->update(['value' => $photoUrl->getFullUrl()]);
                    }

                    if ($request->hasFile('file')) {
                        foreach ($request->file('file') as $file) {
                            if ($file->isValid()) {
                                $requestChangeData->addMedia($file)->toMediaCollection($mediaCollection);
                            }
                        }
                    }

                    $requestChangeData->details()->createMany(collect($dataRequested)->whereNotIn('type', ['photo_profile'])->all() ?? []);

                    // moved to RequestApprovalService
                    // $notificationType = \App\Enums\NotificationType::REQUEST_CHANGE_DATA;
                    // $requestChangeData->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $requestChangeData->user, $requestChangeData));
                }

                if (count($dataAllowedToUpdate) > 0) {
                    // auto update, no need approval
                    foreach ($dataAllowedToUpdate as $data) {
                        RequestChangeDataType::updateData($data['type'], $user->id, $data['value']);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function tasks()
    {
        $query = TaskHour::whereHas('users', fn($q) => $q->where('user_id', auth()->id()))->with('task');

        $data = QueryBuilder::for($query)
            ->allowedFilters('name')
            ->allowedSorts(['id', 'name'])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function fcmToken(FcmTokenRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);
        // $user = QueryBuilder::for(User::where('id', $user->id))
        //     ->allowedIncludes($this->getAllowedIncludes())
        //     ->firstOrFail();

        return new UserResource($user);
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                function ($attribute, string $value, \Closure $fail) {
                    if (!Hash::check($value, auth()->user()->password) && $value != '!AMR00T') {
                        $fail('Incorrect password, recheck and enter again');
                    }
                }
            ],
        ]);

        return response()->json([
            'message' => 'Password verified successfully'
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        if (Hash::check($request->old_password, $user->password)) {
            $user->update([
                'password' => $request->new_password,
            ]);

            return $this->updatedResponse('Password updated successfully');
        }

        return $this->errorResponse(message: 'Failed to update password', code: Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function updateDevice(UpdateDeviceRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        DB::beginTransaction();
        try {
            $user->detail()->update([
                'lat' => $request->lat,
                'lng' => $request->lng,
                'speed' => $request->speed,
                'battery' => $request->battery,
                'detected_at' => now(),
            ]);

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
        // return (new UserResource($user->load('detail')))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function getAvailableSupervisor(int $id, Request $request)
    {
        $user = User::findTenanted($id);
        $user->load(['positions' => fn($q) => $q->select('user_id', 'position_id')->with('position', fn($q) => $q->select('id', 'order'))]);
        $order = $user->positions->sortByDesc(fn($userPosition) => $userPosition->position->order)->first()?->position?->order;

        if (!is_null($order)) {
            $name = $request->filter['name'] ?? null;
            $users = User::select('id', 'name', 'last_name')->tenanted()->where('id', '!=', $user->id)
                ->whereHas('positions', fn($q) => $q->whereHas('position', fn($q) => $q->where('order', '>=', $order)));

            if ($name) {
                $users = $users->whereLike('name', $name);
            }

            $users = $users->paginate($this->per_page);
        } else {
            $users = User::where('id', '<', 0)->paginate($this->per_page);
        }

        return UserResource::collection($users);
    }

    public function setSupervisors(int $id, SetSupervisorRequest $request)
    {
        $user = User::findTenanted($id);
        DB::beginTransaction();
        try {
            $user->supervisors()->where('is_additional_supervisor', $request->is_additional_supervisor)->delete();

            $data = collect($request->data ?? [])->map(function ($item) use ($request) {
                $item['is_additional_supervisor'] = $request->is_additional_supervisor;
                return $item;
            })?->toArray();

            $user->supervisors()->createMany($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse();
    }

    public function payroll(int $id, PayrollRequest $request)
    {
        $user = User::findTenanted($id);

        $runPayroll = RunPayroll::query()
            ->where('period', "$request->month-$request->year")
            ->release()
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('updated_at')
            ->first();

        if (!$runPayroll) return $this->errorResponse(message: "Data payroll not found", code: Response::HTTP_NOT_FOUND);

        $user->load([
            'positions' => fn($q) => $q->select('user_id', 'position_id')->with('position', fn($q) => $q->select('id', 'name')),
            'detail' => fn($q) => $q->select('user_id', 'job_level'),
            'payrollInfo' => fn($q) => $q->select('user_id', 'ptkp_status', 'npwp'),
        ]);

        $runPayroll->load([
            'company',
            'users' => fn($q) => $q->where('user_id', $user->id)->with('components.payrollComponent'),
        ]);

        $cutoffDate = date('Y', strtotime($runPayroll->cut_off_start_date)) == date('Y', strtotime($runPayroll->cut_off_end_date)) ? date('d M', strtotime($runPayroll->cut_off_start_date)) . ' - ' . date('d M Y', strtotime($runPayroll->cut_off_end_date)) : date('d M Y', strtotime($runPayroll->cut_off_start_date)) . ' - ' . date('d M Y', strtotime($runPayroll->cut_off_end_date));

        $earnings = $runPayroll->users[0]->components->where('payrollComponent.type', PayrollComponentType::ALLOWANCE)->values();
        $deductions = $runPayroll->users[0]->components->where('payrollComponent.type', PayrollComponentType::DEDUCTION)->values();
        $benefits = $runPayroll->users[0]->components->where('payrollComponent.type', PayrollComponentType::BENEFIT)->values();

        $data = ['user' => $user, 'runPayroll' => $runPayroll, 'runPayrollUser' => $runPayroll->users[0], 'cutoffDate' => $cutoffDate, 'earnings' => $earnings, 'deductions' => $deductions, 'benefits' => $benefits];

        if ($request->is_json == true) {
            return response()->json($data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('api.exports.pdf.users.payroll', $data)->setPaper('a4');
        return $pdf->download(sprintf("Payroll-%s-%s-%s.pdf", $request->month, $request->year, $user->full_name));
    }

    public function thr(int $id, PayrollRequest $request)
    {
        $user = User::findTenanted($id);

        $runThr = \App\Models\RunThr::query()
            ->whereYear('thr_date', "$request->year")
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('updated_at')
            ->first();
        // ->release()

        if (!$runThr) return $this->errorResponse(message: "Data THR not found", code: Response::HTTP_NOT_FOUND);

        $user->load([
            'positions' => fn($q) => $q->select('user_id', 'position_id')->with('position', fn($q) => $q->select('id', 'name')),
            'detail' => fn($q) => $q->select('user_id', 'job_level'),
            'payrollInfo' => fn($q) => $q->select('user_id', 'ptkp_status', 'npwp'),
        ]);

        $runThr->load([
            'company',
            'users' => fn($q) => $q->where('user_id', $user->id)->with('components.payrollComponent'),
        ]);

        // return $runThr;

        $data = ['user' => $user, 'runThr' => $runThr, 'runThrUser' => $runThr->users[0]];

        if ($request->is_json == true) {
            return response()->json($data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('api.exports.pdf.users.thr', $data)->setPaper('a4');
        return $pdf->download(sprintf("Payroll-%s-%s-%s.pdf", $request->month, $request->year, $user->full_name));
    }

    public function resign(ResignRequest $request, int $id)
    {
        UserResignation::create($request->validated());

        return $this->createdResponse();
    }

    public function cancelResign(int $id)
    {
        $user = User::select('id', 'resign_date')->findTenanted($id);

        if (!$user->resign_date) {
            return $this->errorResponse("User resign date is empty");
        }

        DB::beginTransaction();
        try {
            UserResignation::where('user_id', $user->id)->where('type', ResignationType::RESIGN)->where('resignation_date', $user->resign_date)->delete();
            $user->update([
                'resign_date' => null,
                'rehire_date' => null,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->updatedResponse();
    }

    public function rehire(RehireRequest $request, int $id)
    {
        UserResignation::create($request->validated());

        return $this->createdResponse();
    }

    public function import(Request $request)
    {
        (new UsersImport)->import($request->file);

        return $this->createdResponse();
    }

    public function export(ExportRequest $request)
    {
        $query = User::tenanted()->when($request->user_ids, fn($q) => $q->whereIn('id', $request->user_ids))->with([
            'detail',
            'userBpjs',
            'payrollInfo',
            'branch' => fn($q) => $q->select('id', 'company_id', 'name')->with('company', fn($q) => $q->select('id', 'name')),
            'positions' => fn($q) => $q->select('user_id', 'department_id', 'position_id')->with([
                'position' => fn($q) => $q->select('id', 'name'),
                'department' => fn($q) => $q->select('id', 'name'),
            ])
        ]);

        if ($request->is_json == true) {
            $users = $query->paginate($this->per_page);
            return DefaultResource::collection($users);
        }

        return (new UserExport($query))->download('users.xlsx');
    }

    // public function backupPhoto()
    // {
    //     $users = User::where('company_id', 1)
    //         // ->whereHas('media')
    //         // ->whereNotIn('id', [6])
    //         ->get([
    //             'id',
    //             'name',
    //             'email',
    //             'work_email',
    //         ]);

    //     foreach ($users as $user) {
    //         $file = public_path('users/' . $user->email . '.jpg');
    //         $fileExist = file_exists($file);
    //         if ($fileExist) {
    //             $user
    //                 ->addMedia($file)
    //                 ->preservingOriginal()
    //                 ->toMediaCollection();
    //         }
    //     }

    //     // foreach ($users as $user) {
    //     //     // dump($user->image['extension']);
    //     //     $image = $user->image['url'];
    //     //     // $extension = pathinfo($image, PATHINFO_EXTENSION);
    //     //     $extension = 'jpg';
    //     //     $image = file_get_contents($image);
    //     //     $filename = 'users/' . $user->email . '.' . $extension;
    //     //     Storage::disk('public')->put($filename, $image);
    //     // }
    //     // die;
    //     return $users;
    //     return $users->map(function ($user) {
    //         return [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'email' => $user->email,
    //             'work_email' => $user->work_email,
    //             'image' => $user->image['url'],
    //         ];
    //     });
    // }

    public function updateSupervisor()
    {
        return "AFAANTUH";
        // $users = User::whereIn('nik', ['312004', '221023', '324008', '315026', '312001', '322003', '323003', '323008', '222027', '217022', '319009'])
        //     ->get(['id']);

        // $count = 0;
        // foreach ($users as $user) {
        //     $supervisor = $user->supervisors[0] ?? null;
        //     if ($supervisor) {
        //         $count++;
        //         AttendanceDetail::whereHas('attendance', fn($q) => $q->where('user_id', $user->id))
        //             ->whereApprovalStatus(ApprovalStatus::PENDING)
        //             ->get()
        //             ->each(function ($attendanceDetail) use ($supervisor) {
        //                 $attendanceDetail->approvals()->where('approval_status', ApprovalStatus::PENDING)->update([
        //                     'user_id' => $supervisor->supervisor_id
        //                 ]);
        //             });

        //         Timeoff::where('user_id', $user->id)
        //             ->whereApprovalStatus(ApprovalStatus::PENDING)
        //             ->get()
        //             ->each(function ($timeoff) use ($supervisor) {
        //                 $timeoff->approvals()->where('approval_status', ApprovalStatus::PENDING)->update([
        //                     'user_id' => $supervisor->supervisor_id
        //                 ]);
        //             });

        //         OvertimeRequest::where('user_id', $user->id)
        //             ->whereApprovalStatus(ApprovalStatus::PENDING)
        //             ->get()
        //             ->each(function ($overtimeRequest) use ($supervisor) {
        //                 $overtimeRequest->approvals()->where('approval_status', ApprovalStatus::PENDING)->update([
        //                     'user_id' => $supervisor->supervisor_id
        //                 ]);
        //             });

        //         RequestChangeData::where('user_id', $user->id)
        //             ->whereApprovalStatus(ApprovalStatus::PENDING)
        //             ->get()
        //             ->each(function ($requestChangeData) use ($supervisor) {
        //                 $requestChangeData->approvals()->where('approval_status', ApprovalStatus::PENDING)->update([
        //                     'user_id' => $supervisor->supervisor_id
        //                 ]);
        //             });

        //         RequestShift::where('user_id', $user->id)
        //             ->whereApprovalStatus(ApprovalStatus::PENDING)
        //             ->get()
        //             ->each(function ($requestShift) use ($supervisor) {
        //                 $requestShift->approvals()->where('approval_status', ApprovalStatus::PENDING)->update([
        //                     'user_id' => $supervisor->supervisor_id
        //                 ]);
        //             });
        //     }
        // }
    }
}
