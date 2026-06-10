<?php

namespace App\Http\Services;

use App\Interfaces\Repositories\JobPositionRepositoryInterface;
use App\Interfaces\Services\JobPositionServiceInterface;

class JobPositionService extends BaseService implements JobPositionServiceInterface
{
    public function __construct(protected JobPositionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
