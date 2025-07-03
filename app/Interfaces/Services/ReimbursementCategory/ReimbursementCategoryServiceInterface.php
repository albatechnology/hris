<?php

namespace App\Interfaces\Services\ReimbursementCategory;

use App\Interfaces\Services\BaseServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;

interface ReimbursementCategoryServiceInterface extends BaseServiceInterface
{
    public function addUsers(ReimbursementCategory $reimbursementCategory, array $userIds);
    public function editUsers(ReimbursementCategory $reimbursementCategory, array $data);
    public function deleteUsers(ReimbursementCategory $reimbursementCategory, array $data);
    public function getLimitAmount(ReimbursementCategory|int $reimbursementCategoryId, User|int $userId): int;
    public function getStartEndDate(ReimbursementCategory $reimbursementCategory, string $requestedDate): array;
}
