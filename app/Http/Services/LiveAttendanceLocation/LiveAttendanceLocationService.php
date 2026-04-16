<?php

namespace App\Http\Services\LiveAttendanceLocation;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\LiveAttendanceLocation\LiveAttendanceLocationRepositoryInterface;
use App\Interfaces\Services\LiveAttendanceLocation\LiveAttendanceLocationServiceInterface;

class LiveAttendanceLocationService extends BaseService implements LiveAttendanceLocationServiceInterface
{
    public function __construct(protected LiveAttendanceLocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function createMany(int|string $liveAttendanceId, array $locations)
    {
        foreach ($locations as $location) {
            $this->repository->create(array_merge($location, ['live_attendance_id' => $liveAttendanceId]));
        }
    }
}
