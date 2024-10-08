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
use Illuminate\Support\Facades\Schema;
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
        $data = QueryBuilder::for(Patrol::tenanted()->where(function ($q) {
            $user = auth('sanctum')->user();

            if ($user->parent_id) {
                $q->whereHas('users', function ($q2) use ($user) {
                    $q2->where('user_patrols.user_id', $user->id);
                });

                $q->whereDate('patrols.start_date', '<=', now());
                $q->whereDate('patrols.end_date', '>=', now());
            }
        }))->allowedIncludes(['client'])
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
        return new DefaultResource($patrol->load([
            'users' => [
                'user',
                'userPatrolSchedules.schedule',
            ],
            'patrolLocations' => [
                'clientLocation',
                'tasks',
            ],
        ]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            // patrol
            $patrol = Patrol::create([
                'client_id' => $request->client_id,
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'description' => $request->description,
            ]);

            // user patrol
            if ($request->users) {
                foreach ($request->users as $reqUser) {
                    $userPatrol = $patrol->users()->create([
                        'user_id' => $reqUser['id'],
                    ]);

                    foreach ($reqUser['schedules'] as $reqUserSchedule) {
                        $userPatrol->userPatrolSchedules()->create([
                            'schedule_id' => $reqUserSchedule['id'],
                        ]);
                    }
                }
            }

            // patrol location
            if ($request->locations) {
                foreach ($request->locations as $reqLocation) {
                    $patrolLocation = $patrol->patrolLocations()->create([
                        'client_location_id' => $reqLocation['client_location_id'],
                    ]);

                    foreach ($reqLocation['tasks'] as $reqLocationTask) {
                        $patrolLocation->tasks()->create([
                            'name' => $reqLocationTask['name'],
                            'description' => $reqLocationTask['description'],
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrol->load([
            'users' => [
                'user',
                'userPatrolSchedules.schedule',
            ],
            'patrolLocations' => [
                'clientLocation',
                'tasks',
            ],
        ]));
    }

    public function update(Patrol $patrol, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            // patrol
            $patrol->update([
                'client_id' => $request->client_id,
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'description' => $request->description,
            ]);

            // user patrol
            $patrol->users()->each(fn($userPatrol) => $userPatrol->userPatrolSchedules()->delete());
            $patrol->users()->delete();
            if ($request->users) {
                foreach ($request->users as $reqUser) {
                    $userPatrol = $patrol->users()->create([
                        'user_id' => $reqUser['id'],
                    ]);

                    foreach ($reqUser['schedules'] as $reqUserSchedule) {
                        $userPatrol->userPatrolSchedules()->create([
                            'schedule_id' => $reqUserSchedule['id'],
                        ]);
                    }
                }
            }

            // patrol location
            $patrol->patrolLocations()->each(fn($patrolLocation) => $patrolLocation->tasks()->delete());
            $patrol->patrolLocations()->delete();
            if ($request->locations) {
                foreach ($request->locations as $reqLocation) {
                    $patrolLocation = $patrol->patrolLocations()->create([
                        'client_location_id' => $reqLocation['client_location_id'],
                    ]);

                    foreach ($reqLocation['tasks'] as $reqLocationTask) {
                        $patrolLocation->tasks()->create([
                            'name' => $reqLocationTask['name'],
                            'description' => $reqLocationTask['description'],
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($patrol->load([
            'users' => [
                'user',
                'userPatrolSchedules.schedule',
            ],
            'patrolLocations' => [
                'clientLocation',
                'tasks',
            ],
        ])))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Patrol $patrol)
    {
        try {
            Schema::disableForeignKeyConstraints();
            $patrol->users()->each(fn($userPatrol) => $userPatrol->userPatrolSchedules()->delete());
            $patrol->users()->delete();
            $patrol->patrolLocations()->each(fn($patrolLocation) => $patrolLocation->tasks()->delete());
            $patrol->patrolLocations()->delete();
            $patrol->delete();
            Schema::enableForeignKeyConstraints();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
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
