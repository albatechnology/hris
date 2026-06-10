<?php

namespace App\Http\Repositories;

use App\Interfaces\Repositories\JobPositionRepositoryInterface;
use App\Models\JobPosition;

class JobPositionRepository extends BaseRepository implements JobPositionRepositoryInterface
{
    public function __construct(JobPosition $model)
    {
        parent::__construct($model);
    }
}
