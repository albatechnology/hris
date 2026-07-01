<?php

namespace App\Http\Repositories\Position;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Position\PositionRepositoryInterface;
use App\Models\Position;

class PositionRepository extends BaseRepository implements PositionRepositoryInterface
{
    public function __construct(Position $model)
    {
        parent::__construct($model);
    }
}
