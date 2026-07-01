<?php

namespace App\Http\Services\TaskHour;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\TaskHour\TaskHourRepositoryInterface;
use App\Interfaces\Services\TaskHour\TaskHourServiceInterface;
use App\Models\TaskHour;
use Illuminate\Support\Facades\DB;

class TaskHourService extends BaseService implements TaskHourServiceInterface
{
    public function __construct(protected TaskHourRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): TaskHour
    {
        DB::beginTransaction();

        try {
            $taskHour = $this->repository->create($data);

            if (isset($data['user_ids']) && $data['user_ids']) {
                $taskHour->users()->attach($data['user_ids'], ['task_id' => $taskHour->task_id]);
            }

            DB::commit();

            return $taskHour;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(string $id, array $data): bool
    {
        $taskHour = $this->findByIdOrFail($id);

        DB::beginTransaction();

        try {
            $this->repository->update($id, $data);

            if (isset($data['user_ids']) && $data['user_ids']) {
                $taskHour->users()->syncWithPivotValues($data['user_ids'], ['task_id' => $taskHour->task_id]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function addUsers(string $id, array $userIds): bool
    {
        $taskHour = $this->findByIdOrFail($id);
        $taskHour->users()->attach($userIds, ['task_id' => $taskHour->task_id]);

        return true;
    }

    public function deleteUsers(string $id, array $userIds): bool
    {
        $taskHour = $this->findByIdOrFail($id);
        $taskHour->users()->toggle($userIds);

        return true;
    }
}
