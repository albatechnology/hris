<?php

namespace App\Interfaces\Repositories\Branch;

use App\Interfaces\Repositories\BaseRepositoryInterface;
use App\Models\Branch;

interface BranchRepositoryInterface extends BaseRepositoryInterface
{
    public function findBranchForSummary(int $branchId): ?Branch;
    public function countParentBranches(): int;
    public function countClients(int $branchId, bool $isMain): int;
}
