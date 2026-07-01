<?php

namespace App\Interfaces\Repositories\LiveAttendance;

use App\Interfaces\Repositories\BaseRepositoryInterface;

interface LiveAttendanceRepositoryInterface extends BaseRepositoryInterface
{
    public function createWithRelations(array $data, array $locations = [], array $userIds = []);
    public function updateWithRelations(int|string $id, array $data, array $locations = [], array $userIds = []);
}
