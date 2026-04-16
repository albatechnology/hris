<?php

namespace App\Http\Services\BranchLocation;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\BranchLocation\BranchLocationRepositoryInterface;
use App\Interfaces\Services\BranchLocation\BranchLocationServiceInterface;

class BranchLocationService extends BaseService implements BranchLocationServiceInterface
{
    public function __construct(protected BranchLocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
