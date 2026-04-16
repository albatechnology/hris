<?php

namespace App\Http\Repositories\SupervisorRequestSchedule;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\SupervisorRequestSchedule\SupervisorRequestScheduleRepositoryInterface;
use App\Models\Schedule;

class SupervisorRequestScheduleRepository extends BaseRepository implements SupervisorRequestScheduleRepositoryInterface
{
    public function __construct(Schedule $model)
    {
        parent::__construct($model);
    }
}