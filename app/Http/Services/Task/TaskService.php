<?php

namespace App\Http\Services\Task;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Task\TaskRepositoryInterface;
use App\Interfaces\Services\Task\TaskServiceInterface;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class TaskService extends BaseService implements TaskServiceInterface
{
    public function __construct(protected TaskRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Task
    {
        DB::beginTransaction();
        try {
            $task = $this->repository->create($data);
            if (isset($data['hours']) && $data['hours']) {
                $task->hours()->createMany($data['hours']);
            }
            DB::commit();
            return $task;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(string $id, array $data): bool
    {
        $task = $this->repository->findById($id);

        DB::beginTransaction();
        try {
            $task->update($data);
            if (isset($data['hours']) && $data['hours']) {
                $hours = collect($data['hours']);
                $task->hours()->whereNotIn('id', $hours->pluck('id'))->delete();
                $hours->unique('id')->each(fn($hour) => $task->hours()->updateOrCreate(['id' => $hour['id']], $hour));
            }
            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}