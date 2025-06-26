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
}
