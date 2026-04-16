<?php

namespace App\Http\Repositories\TaskHour;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\TaskHour\TaskHourRepositoryInterface;
use App\Models\TaskHour;

class TaskHourRepository extends BaseRepository implements TaskHourRepositoryInterface
{
    public function __construct(TaskHour $model)
    {
        parent::__construct($model);
    }
}
