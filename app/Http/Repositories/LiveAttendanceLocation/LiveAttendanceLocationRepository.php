<?php

namespace App\Http\Repositories\LiveAttendanceLocation;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\LiveAttendanceLocation\LiveAttendanceLocationRepositoryInterface;
use App\Models\LiveAttendanceLocation;

class LiveAttendanceLocationRepository extends BaseRepository implements LiveAttendanceLocationRepositoryInterface
{
    public function __construct(LiveAttendanceLocation $model)
    {
        parent::__construct($model);
    }
}
