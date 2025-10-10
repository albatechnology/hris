<?php

namespace App\Interfaces\Services\ReimbursementCategory;

use App\Interfaces\Services\BaseServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Illuminate\Support\Collection;

interface ReimbursementCategoryServiceInterface extends BaseServiceInterface
{
    public function addUsers(ReimbursementCategory $reimbursementCategory, Collection $data);
    public function editUsers(ReimbursementCategory $reimbursementCategory, array $data);
    public function deleteUsers(ReimbursementCategory $reimbursementCategory, array $data);
    public function getLimitAmount(ReimbursementCategory|int $reimbursementCategoryId, User|int $userId): int;
    public function getStartEndDate(ReimbursementCategory $reimbursementCategory, string $requestedDate): array;
}
