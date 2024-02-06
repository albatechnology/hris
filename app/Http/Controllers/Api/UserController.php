<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\User\DetailStoreRequest;
use App\Http\Requests\Api\User\PayrollInfoStoreRequest;
use App\Http\Requests\Api\User\StoreRequest;
use App\Http\Requests\Api\User\UpdateRequest;
use App\Http\Resources\User\UserResource;
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
        // $this->middleware('permission:user_access', ['only' => ['index', 'show', 'restore']]);
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
                AllowedFilter::exact('manager_id'),
                AllowedFilter::scope('has_schedule_id'),
                'name', 'email', 'type', 'nik', 'phone', 'marital_status'
            ])
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->allowedSorts([
                'id', 'branch_id', 'manager_id', 'name', 'email', 'type', 'nik', 'phone', 'marital_status', 'created_at'
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }

    public function me()
    {
        /** @var User $user */
        $user = auth()->user();
        $user = QueryBuilder::for(User::findTenanted('id', $user->id))
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->firstOrFail();

        return new UserResource($user);
    }

    public function show(User $user)
    {
        // dump($user->roles);
        // dump($user->permissions);
        // dump($user->getPermissionNames());
        // dump($user->getDirectPermissions());
        // dump($user->getPermissionsViaRoles());
        // dump($user->getAllPermissions());
        // dump($user->getRoleNames());
        // abort_if(!auth()->user()->tokenCan('user_access'), 403);
        $user = QueryBuilder::for(User::where('id', $user->id))
            ->allowedIncludes(self::ALLOWED_INCLUDES)
            ->firstOrFail();

        return new UserResource($user);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create($request->validated());
            $user->roles()->syncWithPivotValues($request->role_ids ?? [], ['group_id' => $user->group_id ?? 1]);

            $companyIds = collect($request->company_ids ?? []);
            if ($user->company_id) $companyIds->push($user->company_id);
            $companyIds = $companyIds->unique()->values()
                ->map(function ($companyId) {
                    return ['company_id' => $companyId];
                })->all();
            $user->companies()->createMany($companyIds);

            $branchIds = collect($request->branch_ids ?? []);
            if ($user->branch_id) $branchIds->push($user->branch_id);
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
            if ($user->company_id) $companyIds->push($user->company_id);
            $companyIds = $companyIds->unique()->values()
                ->map(function ($companyId) {
                    return ['company_id' => $companyId];
                })->all();
            $user->companies()->delete();
            $user->companies()->createMany($companyIds);

            $branchIds = collect($request->branch_ids ?? []);
            if ($user->branch_id) $branchIds->push($user->branch_id);
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
        if ($user->id == 1) return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        // abort_if(!auth()->user()->tokenCan('user_delete'), 403);
        $user->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        if ($id == 1) return response()->json(['message' => 'Admin dengan id 1 tidak dapat dihapus!']);
        // abort_if(!auth()->user()->tokenCan('user_delete'), 403);
        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        // abort_if(!auth()->user()->tokenCan('user_access'), 403);
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

    public function payrollInfo(User $user, PayrollInfoStoreRequest $request)
    {
        if ($user->payrollInfo) {
            $user->payrollInfo->update($request->validated());
        } else {
            $user->payrollInfo()->create($request->validated());
        }
        return new UserResource($user->load('payrollInfo'));
    }
}
