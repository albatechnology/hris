<?php

namespace App\Http\Repositories\Branch;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Branch\BranchRepositoryInterface;
use App\Models\Branch;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }

    public function findBranchForSummary(int $branchId): ?Branch
    {
        return $this->query()
            ->where('id', $branchId)
            ->first(['id', 'is_main']);
    }

    public function countParentBranches(): int
    {
        return $this->query()
            ->whereIsParent()
            ->where('is_main', false)
            ->count();
    }

    public function countClients(int $branchId, bool $isMain): int
    {
        return $this->query()
            ->when(!$isMain, fn ($q) => $q->where('parent_id', $branchId))
            ->whereIsParent(false)
            ->count();
    }
}
