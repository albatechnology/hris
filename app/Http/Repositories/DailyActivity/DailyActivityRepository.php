<?php

namespace App\Http\Repositories\DailyActivity;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\DailyActivity\DailyActivityRepositoryInterface;
use App\Models\DailyActivity;

class DailyActivityRepository extends BaseRepository implements DailyActivityRepositoryInterface
{
    public function __construct(DailyActivity $model)
    {
        parent::__construct($model);
    }
}
