<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Patrol\StoreRequest;
use App\Http\Requests\Api\Patrol\UserStoreRequest;
use App\Http\Requests\Api\Patrol\UserUpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Patrol;
use App\Models\UserPatrol;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatrolController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Patrol::tenanted())
            ->allowedIncludes(['client'])
            ->allowedFilters([
                AllowedFilter::callback('has_user_id', function ($query, $value) {
                    $query->whereHas('users', fn($q) => $q->where('user_id', $value));
                }),
                AllowedFilter::exact('id'),
                AllowedFilter::exact('client_id'),
                'name',
                'start_date',
                'end_date',
            ])
            ->allowedSorts([
                'id',
                'client_id',
                'name',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Patrol $patrol)
    {
        $patrol->load('client');
        return new DefaultResource($patrol);
    }

    public function store(StoreRequest $request)
    {
        try {
            $patrol = Patrol::create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrol);
    }

    public function update(Patrol $patrol, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $patrol->update($request->validated());

            $userPatrols = [];
            foreach ($request->users?->ids ?? [] as $userId) {
                $userPatrols[] = [
                    'patrol_id' => $patrol->id,
                    'user_id' => $userId,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time
                ];
            }

            $patrol->users()->createMany($userPatrols);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($patrol))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Patrol $patrol)
    {
        try {
            $patrol->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $patrol = Patrol::withTrashed()->findOrFail($id);

        try {
            $patrol->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $patrol = Patrol::withTrashed()->findOrFail($id);

        try {
            $patrol->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrol);
    }

    public function userIndex(int $patrolId)
    {
        $data = QueryBuilder::for(UserPatrol::where('patrol_id', $patrolId)->with('user'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('patrol_id'),
                'start_time',
                'end_time',
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'patrol_id',
                'start_time',
                'end_time',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function userShow(int $patrolId, int $userPatrolId)
    {
        $userPatrol = UserPatrol::where('id', $userPatrolId)->where('patrol_id', $patrolId)->with('patrol', 'user')->firstOrFail();

        return new DefaultResource($userPatrol);
    }

    public function userStore(int $patrolId, UserStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $userPatrols = [];
            foreach ($request->user_ids ?? [] as $userId) {
                $userPatrols[] = [
                    'patrol_id' => $patrolId,
                    'user_id' => $userId,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            UserPatrol::insert($userPatrols);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function userUpdate(int $patrolId, int $userPatrolId, UserUpdateRequest $request)
    {
        $userPatrol = UserPatrol::where('id', $userPatrolId)->where('patrol_id', $patrolId)->firstOrFail();

        try {
            $userPatrol->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse();
    }

    public function userDestroy(int $patrolId, int $userPatrolId)
    {
        $userPatrol = UserPatrol::where('id', $userPatrolId)->where('patrol_id', $patrolId)->firstOrFail();

        try {
            $userPatrol->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }
}
