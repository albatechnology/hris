<?php

namespace App\Http\Services\LockAttendance;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\LockAttendance\LockAttendanceRepositoryInterface;
use App\Interfaces\Services\LockAttendance\LockAttendanceServiceInterface;

class LockAttendanceService extends BaseService implements LockAttendanceServiceInterface
{
    public function __construct(protected LockAttendanceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
