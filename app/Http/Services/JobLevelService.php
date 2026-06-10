<?php

namespace App\Http\Services;

use App\Interfaces\Repositories\JobLevelRepositoryInterface;
use App\Interfaces\Services\JobLevelServiceInterface;

class JobLevelService extends BaseService implements JobLevelServiceInterface
{
    public function __construct(protected JobLevelRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
