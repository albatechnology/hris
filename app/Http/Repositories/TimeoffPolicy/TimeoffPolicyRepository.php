<?php

namespace App\Http\Repositories\TimeoffPolicy;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\TimeoffPolicy\TimeoffPolicyRepositoryInterface;
use App\Models\TimeoffPolicy;

class TimeoffPolicyRepository extends BaseRepository implements TimeoffPolicyRepositoryInterface
{
    public function __construct(TimeoffPolicy $model)
    {
        parent::__construct($model);
    }
}
