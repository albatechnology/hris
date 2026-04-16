<?php

namespace App\Http\Repositories\LockAttendance;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\LockAttendance\LockAttendanceRepositoryInterface;
use App\Models\LockAttendance;

class LockAttendanceRepository extends BaseRepository implements LockAttendanceRepositoryInterface
{
    public function __construct(LockAttendance $model)
    {
        parent::__construct($model);
    }
}
