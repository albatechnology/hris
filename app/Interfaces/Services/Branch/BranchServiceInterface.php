<?php

namespace App\Interfaces\Services\Branch;

use App\Interfaces\Services\BaseServiceInterface;

interface BranchServiceInterface extends BaseServiceInterface
{
    public function summary(?int $branchId): array;
}
