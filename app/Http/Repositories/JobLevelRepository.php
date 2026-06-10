<?php

namespace App\Http\Repositories;

use App\Interfaces\Repositories\JobLevelRepositoryInterface;
use App\Models\JobLevel;

class JobLevelRepository extends BaseRepository implements JobLevelRepositoryInterface
{
    public function __construct(JobLevel $model)
    {
        parent::__construct($model);
    }
}
