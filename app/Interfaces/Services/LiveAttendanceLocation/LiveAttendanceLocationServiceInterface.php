<?php

namespace App\Interfaces\Services\LiveAttendanceLocation;

use App\Interfaces\Services\BaseServiceInterface;

interface LiveAttendanceLocationServiceInterface extends BaseServiceInterface
{
    public function createMany(int|string $liveAttendanceId, array $locations);
}
