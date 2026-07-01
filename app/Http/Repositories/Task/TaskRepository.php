<?php

namespace App\Http\Repositories\Task;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Task\TaskRepositoryInterface;
use App\Models\Task;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }
}