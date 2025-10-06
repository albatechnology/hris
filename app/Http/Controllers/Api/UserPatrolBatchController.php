<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DefaultResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\UserPatrolBatch;
use App\Http\Requests\Api\UserPatrolBatch\StoreRequest;
use App\Http\Requests\Api\UserPatrolBatch\SyncRequest;
use App\Http\Requests\Api\UserPatrolBatch\UpdateRequest;
use App\Models\UserPatrolMovement;
use App\Models\UserPatrolTask;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPatrolBatchController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:panic_access', ['only' => ['restore']]);
        // $this->middleware('permission:panic_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:panic_create', ['only' => 'store']);
        // $this->middleware('permission:panic_edit', ['only' => 'update']);
        // $this->middleware('permission:panic_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(Request $request)
    {
        $startDate = $request->filter['start_date'] ?? date('Y-m-d');
        $endDate = $request->filter['end_date'] ?? $startDate;

        $data = QueryBuilder::for(
            UserPatrolBatch::with(['user' => fn($q) => $q->select('id', 'name', 'nik')])
                ->where(fn($q) => $q->whereDate('datetime', '>=', $startDate)->whereDate('datetime', '<=', $endDate))
        )
            ->allowedIncludes([
                'patrol'
            ])
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('patrol_id'),
            ])
            ->allowedSorts([
                'id',
                'patrol_id',
                'user_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function sync(SyncRequest $request)
    {
        DB::beginTransaction();
        try {
            $userPatrolBatch = UserPatrolBatch::create($request->validated());

            foreach ($request->tasks ?? [] as $task) {
                // $userPatrolTask = $userPatrolBatch->userPatrolTasks()->create([
                //     'user_patrol_batch_id' => $userPatrolBatch->id,
                //     ...$task
                // ]);

                $userPatrolTask = UserPatrolTask::create([
                    'user_patrol_batch_id' => $userPatrolBatch->id,
                    ...$task
                ]);
                foreach ($task['images'] ?? [] as $image) {
                    if ($image->isValid()) {
                        $userPatrolTask->addMedia($image)->toMediaCollection();
                    }
                }
            }

            foreach ($request->locations ?? [] as $location) {
                // $userPatrolBatch->userPatrolMovements()->create([
                //     'user_patrol_batch_id' => $userPatrolBatch->id,
                //     ...$location
                // ]);
                UserPatrolMovement::create([
                    'user_patrol_batch_id' => $userPatrolBatch->id,
                    ...$location
                ]);
            }

            $endAt = $request->locations[count($request->locations)-1];
            $userPatrolBatch->update(['end_at' => $endAt['datetime'] ?? null]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function store(StoreRequest $request)
    {
        $userPatrolBatch = UserPatrolBatch::create($request->validated());

        return new DefaultResource($userPatrolBatch);
    }

    public function show(int $id)
    {
        $userPatrolBatch = UserPatrolBatch::findTenanted($id);

        $userPatrolBatch->load([
            'patrol',
            'user' => fn($q) => $q->select('id', 'name', 'nik')
        ]);

        return new DefaultResource($userPatrolBatch);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $userPatrolBatch = UserPatrolBatch::findTenanted($id);
        $userPatrolBatch->update($request->validated());

        return new DefaultResource($userPatrolBatch);
    }

    public function destroy(int $id)
    {
        $userPatrolBatch = UserPatrolBatch::findTenanted($id);
        $userPatrolBatch->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $userPatrolBatch = UserPatrolBatch::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            UserPatrolTask::select('id')->where('user_patrol_batch_id', $id)->get()->each(function ($userPatrolTask) {
                $userPatrolTask->media()->forceDelete();
                $userPatrolTask->forceDelete();
            });
            UserPatrolMovement::where('user_patrol_batch_id', $id)->forceDelete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        $userPatrolBatch->forceDelete();

        return $this->deletedResponse();
    }
}
