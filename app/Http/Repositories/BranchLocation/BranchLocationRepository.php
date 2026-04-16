<?php

namespace App\Http\Repositories\BranchLocation;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\BranchLocation\BranchLocationRepositoryInterface;
use App\Models\BranchLocation;

class BranchLocationRepository extends BaseRepository implements BranchLocationRepositoryInterface
{
    public function __construct(BranchLocation $model)
    {
        parent::__construct($model);
    }
}
