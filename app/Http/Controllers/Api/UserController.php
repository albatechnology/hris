<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Enums\RequestChangeDataType;
use App\Enums\UserType;
use App\Http\Requests\Api\User\DetailStoreRequest;
use App\Http\Requests\Api\User\RegisterRequest;
use App\Http\Requests\Api\User\StoreRequest;
use App\Http\Requests\Api\User\UpdateRequest;
use App\Http\Requests\Api\User\UploadPhotoRequest;
use App\Http\Resources\Branch\BranchResource;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\User\UserResource;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends BaseController
{
    const ALLOWED_INCLUDES = ['roles', 'detail', 'payrollInfo', 'experiences', 'educations', 'contacts', 'companies', 'branches', 'schedules'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:user_access', ['only' => ['restore']]);
        $this->middleware('permission:user_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:user_create', ['only' => 'store']);
        $this->middleware('permission:user_edit', ['only' => 'update']);
        $this->middleware('permission:user_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $users = QueryBuilder::for(User::tenanted()->with(['roles' => fn ($q) => $q->select('id', 'name')]))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('approval_id'),
                AllowedFilter::scope('has_schedule_id'),
                'name', 'email', 'type', 'nik', 'phone',
            ])
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->allowedSorts([
                'id', 'branch_id', 'parent_id', 'approval_id', 'name', 'email', 'type', 'nik', 'phone', 'created_at',
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }

    public function me()
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $user = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->firstOrFail();

        return new UserResource($user);
    }

    public function show(User $user)
    {
        $user = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(self::ALLOWED_INCLUDES)
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
            $branches = Branch::whereHas('company', fn ($q) => $q->where('group_id', $user->group_id))->get();
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
                    'type' => $requestChangeDataAllow->type,
                    'value' => $value,
                ];
            }
        }

        DB::beginTransaction();
        try {
            /** @var \App\Models\RequestChangeData $requestChangeData */
            $requestChangeData = $user->requestChangeDatas()->create($request->validated());

            if (count($dataRequested) > 0) {
                if ($request->hasFile('file')) {
                    $mediaCollection = MediaCollection::REQUEST_CHANGE_DATA->value;
                    foreach ($request->file as $file) {
                        if ($file->isValid()) {
                            $requestChangeData->addMedia($file)->toMediaCollection($mediaCollection);
                        }
                    }
                }

                $requestChangeData->details()->createMany($dataRequested);

                $notificationType = \App\Enums\NotificationType::REQUEST_CHANGE_DATA;
                $requestChangeData->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $requestChangeData->user, $requestChangeData));
            }

            if (count($dataAllowedToUpdate) > 0) {
                // auto update, no need approval
                foreach ($dataAllowedToUpdate as $data) {
                    RequestChangeDataType::updateData($data['type'], $user->id, $data['value']);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }
}
