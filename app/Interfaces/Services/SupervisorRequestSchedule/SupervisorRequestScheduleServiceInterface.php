<?php

namespace App\Interfaces\Services\SupervisorRequestSchedule;

use App\Interfaces\Services\BaseServiceInterface;

interface SupervisorRequestScheduleServiceInterface extends BaseServiceInterface
{
    public function approve(string $id, array $data): void;
}