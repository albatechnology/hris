<?php

namespace App\Interfaces\Services\UserExperience;

use App\Interfaces\Services\BaseServiceInterface;

interface UserExperienceServiceInterface extends BaseServiceInterface
{
    public function findByUser(int $userId, int $id);
    public function createForUser(int $userId, array $data);
    public function updateForUser(int $userId, int $id, array $data);
}
