<?php

namespace App\Interfaces\Services\UserCustomField;

use App\Interfaces\Services\BaseServiceInterface;

interface UserCustomFieldServiceInterface extends BaseServiceInterface
{
    public function listByUser(int $userId);
    public function findByUser(int $userId, int $id);
    public function createForUser(int $userId, array $data);
    public function updateForUser(int $userId, int $id, array $data);
}
