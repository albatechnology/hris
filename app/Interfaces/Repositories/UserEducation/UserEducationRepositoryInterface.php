<?php

namespace App\Interfaces\Repositories\UserEducation;

use App\Interfaces\Repositories\BaseRepositoryInterface;

interface UserEducationRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUser(int $userId);
    public function findByUserAndId(int $userId, int $id);
    public function createForUser(int $userId, array $data);
}
