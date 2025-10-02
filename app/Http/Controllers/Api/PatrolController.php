<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Patrol;
use App\Models\PatrolTask;
use App\Models\UserPatrol;
use Illuminate\Http\Response;
use App\Models\PatrolLocation;
use App\Models\UserPatrolBatch;
use App\Exports\PatrolTaskExport;
use Illuminate\Support\Facades\DB;
use App\Exports\TestPatrolTaskExport;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\DefaultResource;
use Spatie\QueryBuilder\AllowedInclude;
use App\Http\Requests\Api\Patrol\StoreRequest;
use App\Http\Requests\Api\Patrol\UserIndexRequest;
use App\Http\Requests\Api\Patrol\UserStoreRequest;
use App\Http\Requests\Api\Patrol\UserUpdateRequest;

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

    private function getAllowedIncludes()
    {
        return [
            'patrolHours',
            AllowedInclude::callback('branch', function ($query) {
                $query->selectMinimalist();
            }),
            AllowedInclude::callback('users', function ($query) {
                $query->with('user', fn($q) => $q->select('id', 'name', 'nik'));
            }),
            AllowedInclude::callback('patrolLocations', function ($query) {
                $query->select('id', 'patrol_id', 'branch_location_id', 'description')
                    ->with([
                        'branchLocation' => fn($q) => $q->select('id', 'branch_id', 'name', 'lat', 'lng', 'address', 'description'),
                        'tasks' => fn($q) => $q->select('id', 'patrol_location_id', 'name', 'description'),
                    ]);
            }),
        ];
    }

    public function index()
    {
        $data = QueryBuilder::for(Patrol::tenanted())
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedFilters([
                AllowedFilter::callback('has_user_id', function ($query, $value) {
                    $query->whereHas('users', fn($q) => $q->where('user_id', $value));
                }),
                AllowedFilter::exact('branch_id'),
                'name',
                'start_date',
                'end_date',
            ])
            ->allowedSorts([
                'id',
                'branch_id',
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

    public function show(int $id)
    {
        $patrol = QueryBuilder::for(
            Patrol::selectMinimalist()
                ->tenanted()->where('id', $id)
        )
            ->allowedIncludes($this->getAllowedIncludes())
            ->firstOrFail();

        return new DefaultResource($patrol);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            // patrol
            $patrol = Patrol::create([
                'branch_id' => $request->branch_id,
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'description' => $request->description,
            ]);

            $patrol->patrolHours()->createMany($request->hours);
            $patrol->users()->createMany(collect($request->users)->map(fn($id) => [
                'user_id' => $id,
            ]));

            // patrol location
            if ($request->locations) {
                foreach ($request->locations as $reqLocation) {
                    $patrolLocation = $patrol->patrolLocations()->create([
                        'branch_location_id' => $reqLocation['branch_location_id'],
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
                // 'userPatrolSchedules.schedule',
            ],
            'patrolLocations' => [
                'branchLocation',
                'tasks',
            ],
        ]));
    }

    public function update(StoreRequest $request, int $id)
    {
        $patrol = Patrol::where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            // patrol
            $patrol->update([
                'branch_id' => $request->branch_id,
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'description' => $request->description,
            ]);

            $patrol->patrolHours()->delete();
            $patrol->patrolHours()->createMany($request->hours);

            $patrol->users()->delete();
            $patrol->users()->createMany(collect($request->users)->map(fn($id) => [
                'user_id' => $id,
            ]));

            $patrolLocationIds = $patrol->patrolLocations->pluck('id');
            $patrolTaskIds = $patrol->patrolLocations->pluck('tasks')->flatten(1)->pluck('id');
            $updatedPatrolLocationIds = [];
            $updatedPatrolTaskIds = [];
            foreach ($request->locations ?? [] as $location) {
                if (!empty($location['id'])) {
                    $updatedPatrolLocationIds[] = $location['id'];
                    $patrolLocation = PatrolLocation::findOrFail($location['id']);
                    $patrolLocation->update([
                        'branch_location_id' => $location['branch_location_id'],
                    ]);
                } else {
                    $patrolLocation = $patrol->patrolLocations()->create([
                        'branch_location_id' => $location['branch_location_id'],
                    ]);
                }

                foreach ($location['tasks'] ?? [] as $task) {
                    if (!empty($task['id'])) {
                        $updatedPatrolTaskIds[] = $task['id'];
                        $patrolTask = PatrolTask::findOrFail($task['id']);
                        $patrolTask->update([
                            'name' => $task['name'],
                            'description' => $task['description'],
                        ]);
                    } else {

                        $patrolLocation->tasks()->create([
                            'name' => $task['name'],
                            'description' => $task['description'],
                        ]);
                    }
                }
            }

            $patrol->patrolLocations->each(function ($patrolLocation) use ($patrolTaskIds, $updatedPatrolTaskIds) {
                $patrolLocation->tasks
                    ->whereIn('id', $patrolTaskIds)
                    ->whereNotIn('id', $updatedPatrolTaskIds)
                    ->each(fn(PatrolTask $patrolTask) => $patrolTask->userPatrolTasks()->delete());

                $patrolLocation->tasks()
                    ->whereIn('id', $patrolTaskIds)
                    ->whereNotIn('id', $updatedPatrolTaskIds)
                    ->delete();
            });

            $patrol->patrolLocations()
                ->whereIn('id', $patrolLocationIds)
                ->whereNotIn('id', $updatedPatrolLocationIds)
                ->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($patrol->load([
            'users' => [
                'user',
                // 'userPatrolSchedules.schedule',
            ],
            'patrolLocations' => [
                'branchLocation',
                'tasks',
            ],
        ])))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $patrol = Patrol::where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            Schema::disableForeignKeyConstraints();
            // $patrol->users()->each(fn($userPatrol) => $userPatrol->userPatrolSchedules()->delete());
            $patrol->users()->delete();
            $patrol->patrolLocations()->each(fn($patrolLocation) => $patrolLocation->tasks()->delete());
            $patrol->patrolLocations()->delete();
            $patrol->patrolHours()->delete();
            $patrol->delete();
            Schema::enableForeignKeyConstraints();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function userIndex(UserIndexRequest $request, int $patrolId)
    {
        $startDate = $request->filter['start_date'] ?? null;
        $endDate = $request->filter['end_date'] ?? null;
        $data = QueryBuilder::for(
            UserPatrol::where('patrol_id', $patrolId)
                ->with(['user' => function($query) use($patrolId){
                    $query->select('id','name','nik')
                        ->withMax('patrolBatches as last_activity', 'created_at');
                }])
                ->when(
                    $startDate && $endDate,
                    fn($q) => $q->whereHas('user', fn($q) => $q->whereHas(
                        'patrolBatches',
                        fn($q) => $q->whereDate('datetime', '>=', $startDate)->whereDate('datetime', '<=', $endDate)
                    ))
                )
        )
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('patrol_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->whereHas('user', fn($q) => $q->whereLike('name', $value));
                }),
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

            $sorted = $data->getCollection()
            ->sortByDesc(fn($userPatrol) => $userPatrol->user->last_activity)
            ->values();

            $data->setCollection($sorted);

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

    public function export(UserIndexRequest $request, int $id)
    {
        $startDate = $request->filter['start_date'] ?? date('Y-m-d');
        $endDate = $request->filter['end_date'] ?? date('Y-m-d');
        $patrol = Patrol::selectMinimalist(['created_at'])->where('id', $id)->firstOrFail();

        $patrol->load([
            'patrolLocations' => function ($q) {
                $q->select('id', 'patrol_id', 'branch_location_id', 'description')
                    ->with([
                        'branchLocation' => fn($q) => $q->select('id', 'name', 'lat', 'lng', 'address'),
                        'tasks' => fn($q) => $q->select('id', 'patrol_location_id', 'name', 'description')
                    ]);
            },
            'users' => fn($q) => $q->with(
                'user',
                fn($q) => $q->select('id', 'name', 'nik')
                    ->with('patrolBatches', function ($q) use ($id, $startDate, $endDate) {
                        $q->where('patrol_id', $id)
                            ->whereDate('datetime', '>=', $startDate)->whereDate('datetime', '<=', $endDate)
                            ->with(
                                'userPatrolTasks',
                                fn($q) => $q->with([
                                    'media',
                                    'patrolTask' => fn($q) => $q->select('id', 'patrol_location_id', 'name')->with('patrolLocation', fn($q) => $q->select('id', 'branch_location_id')->with('branchLocation', fn($q) => $q->select('id', 'name'))),
                                ])
                            );
                    })
            ),
        ]);

        return (new PatrolTaskExport($patrol, $startDate, $endDate))->download('new-report-patroli.xls', \Maatwebsite\Excel\Excel::HTML, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    public function testExport()
    {
        if (request()->has('preview')) {
            $userPatrolBatch = UserPatrolBatch::with('userPatrolTasks.media')->find(449);
            return $userPatrolBatch;
        }
        return (new TestPatrolTaskExport(449))->download('test-report-patroli.xls', \Maatwebsite\Excel\Excel::HTML);
    }

    // public function export(Request $request, int $id)
    // {
    //     $date = $request->filter['date'] ?? date('Y-m-d');
    //     $patrol = Patrol::where('id',$id)->firstOrFail();;
    //     $patrol->load([
    //         'patrolLocations' => function ($q) use ($date) {
    //             $q
    //                 ->with('tasks', function ($q) use ($date) {
    //                     $q
    //                         ->select('id', 'patrol_location_id', 'name', 'description')
    //                         ->with('userPatrolTasks', function ($q) use ($date) {
    //                             $q->whereDate('created_at', $date)
    //                                 // ->with('user', fn($q) => $q->select('id', 'name'))
    //                                 ->with('schedule', fn($q) => $q->select('id', 'name'))
    //                                 ->with('shift', fn($q) => $q->select('id', 'name'));
    //                         });
    //                 });
    //         }
    //     ]);

    //     return (new PatrolTaskExport($patrol))->download('report-patroli.xlsx');

    //     return $patrol;
    // }

    public function usersLocation()
    {
        $query = QueryBuilder::for(
            User::tenanted(request()->filter['is_my_descendant'] ?? false)
                ->select('id', 'name')
                ->with('detail', fn($q) => $q->select('user_id', 'detected_at', 'lat', 'lng'))
        )
            ->allowedFilters([
                AllowedFilter::exact('branch_id'),
                AllowedFilter::scope('name', 'whereName'),
            ])
            ->orderByDesc(
                \App\Models\UserDetail::select('detected_at')
                    ->whereColumn('user_id', 'users.id')
                    ->latest()
                    ->take(1)
            );

        $users = $query->paginate($this->per_page);
        return DefaultResource::collection($users);
    }
}
