<?php

namespace App\Interfaces\Services\Reimbursement;

use App\Interfaces\Services\BaseServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;

interface ReimbursementServiceInterface extends BaseServiceInterface
{
    public function getTotalReimbursementTaken(User|int $userId, ReimbursementCategory|int|null $reimbursementCategoryId, ?string $startDate = null, ?string $endDate = null): int;
}
