<?php

namespace App\Interfaces\Repositories\UserExperience;

use App\Interfaces\Repositories\BaseRepositoryInterface;

interface UserExperienceRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUserAndId(int $userId, int $id);
    public function createForUser(int $userId, array $data);
}
