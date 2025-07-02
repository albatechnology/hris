<?php

namespace App\Interfaces\Services\Reimbursement;

use App\Interfaces\Services\BaseServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;

interface ReimbursementServiceInterface extends BaseServiceInterface
{
    public function getTotalReimbursementTaken(ReimbursementCategory|int $reimbursementCategoryId, User|int $userId, ?string $startDate = null, ?string $endDate = null): int;
}
