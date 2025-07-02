<?php

namespace App\Interfaces\Services\ReimbursementCategory;

use App\Interfaces\Services\BaseServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;

interface ReimbursementCategoryServiceInterface extends BaseServiceInterface
{
    public function getLimitAmount(ReimbursementCategory|int $reimbursementCategory, User|int $user): int;
    public function getStartEndDate(ReimbursementCategory $reimbursementCategory, string $requestedDate): array;
}
