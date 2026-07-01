<?php

namespace App\Interfaces\Services\TaskHour;

use App\Interfaces\Services\BaseServiceInterface;

interface TaskHourServiceInterface extends BaseServiceInterface
{
    public function addUsers(string $id, array $userIds): bool;
    public function deleteUsers(string $id, array $userIds): bool;
}
