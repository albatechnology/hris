<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\MediaCollection;
use App\Enums\PayrollComponentType;
use App\Enums\RequestChangeDataType;
use App\Enums\UserType;
use App\Http\Requests\Api\User\DetailStoreRequest;
use App\Http\Requests\Api\User\RegisterRequest;
use App\Http\Requests\Api\User\StoreRequest;
use App\Http\Requests\Api\User\UpdateRequest;
use App\Http\Requests\Api\User\UploadPhotoRequest;
use App\Http\Requests\Api\User\FcmTokenRequest;
use App\Http\Requests\Api\User\PayrollRequest;
use App\Http\Requests\Api\User\SetSupervisorRequest;
use App\Http\Requests\Api\User\UpdateDeviceRequest;
use App\Http\Requests\Api\User\UpdatePasswordRequest;
use App\Http\Resources\Branch\BranchResource;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\User\UserResource;
use App\Imports\UserSunImport;
use App\Models\Branch;
use App\Models\Company;
use App\Models\TaskHour;
use App\Models\User;
use Carbon\Carbon;
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
            'detail',
            'payrollInfo',
            'schedules',
            'supervisors',
        ];
    }

    public function index()
    {
        $users = QueryBuilder::for(
            User::tenanted(request()->filter['is_my_descendant'] ?? false)
                ->with(['roles' => fn($q) => $q->select('id', 'name'), 'patrols.client'])
        )
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('approval_id'),
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
                'parent_id',
                'approval_id',
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

    public function show(User $user)
    {
        $user = QueryBuilder::for(User::tenanted()->where('id', $user->id))
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new UserResource($user);
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create($request->validated());
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

            $notificationType = \App\Enums\NotificationType::SETUP_PASSWORD;
            $user->notify(new ($notificationType->getNotificationClass())($notificationType));

            DB::commit();
        } catch (\Exception $th) {
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
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new UserResource($user);
    }

    public function update(User $user, UpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $user->update($request->validated());
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
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new UserResource($user))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(User $user)
    {
        if ($user->id == 1) {
            return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        }
        $user->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        if ($id == 1) {
            return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        }
        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return new UserResource($user);
    }

    public function detail(User $user, DetailStoreRequest $request)
    {
        if ($user->detail) {
            $user->detail->update($request->validated());
        } else {
            $user->detail()->create($request->validated());
        }

        return new UserResource($user->load('detail'));
    }

    public function companies(User $user)
    {
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

    public function branches(User $user)
    {
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
        } catch (\Exception $e) {
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

                $mediaCollection = MediaCollection::REQUEST_CHANGE_DATA->value;
                $photoProfile = collect($dataRequested)->firstWhere('type', 'photo_profile');

                if ($photoProfile && $photoProfile['value']?->isValid()) {
                    $requestChangeDataDetail = $requestChangeData->details()->create($photoProfile);

                    $photoUrl = $user->addMedia($photoProfile['value'])->toMediaCollection($mediaCollection);
                    $requestChangeDataDetail->update(['value' => $photoUrl->getFullUrl()]);
                }

                $requestChangeData->details()->createMany(collect($dataRequested)->whereNotIn('type', ['photo_profile'])->all() ?? []);
            } else {
                /** @var \App\Models\RequestChangeData $requestChangeData */
                $requestChangeData = $user->requestChangeDatas()->create($request->validated());

                if (count($dataRequested) > 0) {
                    $mediaCollection = MediaCollection::REQUEST_CHANGE_DATA->value;
                    $photoProfile = collect($dataRequested)->firstWhere('type', 'photo_profile');

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
        } catch (\Exception $e) {
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
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return (new UserResource($user->load('detail')))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    // public function getAvailableSupervisor(int $positionId)
    // {
    //     $position = Position::findOrFail($positionId, ['id', 'order']);

    //     $users = User::select('id', 'name')->tenanted()
    //         ->whereHas('positions', fn($q) => $q->whereHas('position', fn($q) => $q->where('order', '>=', $position->order)))
    //         ->with('userPositions', fn($q) => $q->select('id', 'order')->orderByDesc('order'))
    //         ->paginate();

    //     $users->map(function ($user) {
    //         $user->position_id = $user->userPositions[0]->id;
    //     });

    //     return UserResource::collection($users);
    // }

    public function getAvailableSupervisor(User $user)
    {
        $user->load(['positions' => fn($q) => $q->select('user_id', 'position_id')->with('position', fn($q) => $q->select('id', 'order'))]);
        $order = $user->positions->sortByDesc(fn($userPosition) => $userPosition->position->order)->first()?->position?->order;

        if (!is_null($order)) {
            $users = User::select('id', 'name')->tenanted()->where('id', '!=', $user->id)
                ->whereHas('positions', fn($q) => $q->whereHas('position', fn($q) => $q->where('order', '>=', $order)))
                ->paginate();
        } else {
            $users = User::where('id', '<', 0)->paginate();
        }

        return UserResource::collection($users);
    }

    public function setSupervisors(User $user, SetSupervisorRequest $request)
    {
        $user->supervisors()->createMany($request->data);

        return $this->updatedResponse();
    }

    public function payroll(User $user, PayrollRequest $request)
    {
        $user->load(['runPayrollUser' => fn($q) => $q->whereHas('runPayroll', fn($q) => $q->where('period', "$request->month-$request->year"))]);

        if ($user->runPayrollUser->count() <= 0) return $this->errorResponse(message: "Data payroll not found", code: Response::HTTP_NOT_FOUND);

        $user->load([
            'positions' => fn($q) => $q->select('user_id', 'position_id')->with('position', fn($q) => $q->select('id', 'name')),
            'detail' => fn($q) => $q->select('user_id', 'job_level'),
            'payrollInfo' => fn($q) => $q->select('user_id', 'ptkp_status', 'npwp'),
        ]);

        $runPayrollUser = $user->runPayrollUser[0]->load([
            'runPayroll.company',
            'components.payrollComponent',
        ]);

        $cutoffDate = date('Y', strtotime($runPayrollUser->runPayroll->cutoff_start_date)) == date('Y', strtotime($runPayrollUser->runPayroll->cutoff_end_date)) ? date('d M', strtotime($runPayrollUser->runPayroll->cutoff_start_date)) . ' - ' . date('d M Y', strtotime($runPayrollUser->runPayroll->cutoff_end_date)) : date('d M Y', strtotime($runPayrollUser->runPayroll->cutoff_start_date)) . ' - ' . date('d M Y', strtotime($runPayrollUser->runPayroll->cutoff_end_date));

        $earnings = $runPayrollUser->components->where('payrollComponent.type', PayrollComponentType::ALLOWANCE);
        $deductions = $runPayrollUser->components->where('payrollComponent.type', PayrollComponentType::DEDUCTION);
        $benefits = $runPayrollUser->components->where('payrollComponent.type', PayrollComponentType::BENEFIT);

        $data = ['user' => $user, 'runPayrollUser' => $runPayrollUser, 'cutoffDate' => $cutoffDate, 'earnings' => $earnings, 'deductions' => $deductions, 'benefits' => $benefits];

        if ($request->is_json == true) {
            return response()->json($data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('api.exports.pdf.users.payroll', $data)->setPaper('a4');
        return $pdf->download(sprintf("Payroll-%s-%s-%s.pdf", $request->month, $request->year, $user->full_name));
    }

    public function import(Request $request)
    {
        (new UserSunImport)->import($request->file);

        return 'oke';
    }
}
