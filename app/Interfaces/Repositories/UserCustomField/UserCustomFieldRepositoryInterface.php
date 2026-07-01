<?php

namespace App\Interfaces\Repositories\UserCustomField;

use App\Interfaces\Repositories\BaseRepositoryInterface;

interface UserCustomFieldRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUserAndId(int $userId, int $id);
    public function existsByUserAndCustomField(int $userId, int $customFieldId): bool;
    public function createForUser(int $userId, array $data);
}
