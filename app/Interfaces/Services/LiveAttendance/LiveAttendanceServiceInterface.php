<?php

namespace App\Interfaces\Services\LiveAttendance;

use App\Interfaces\Services\BaseServiceInterface;

interface LiveAttendanceServiceInterface extends BaseServiceInterface
{
    public function createWithRelations(array $data, array $locations = [], array $userIds = []);
    public function updateWithRelations(int|string $id, array $data, array $locations = [], array $userIds = []);
}
