<?php

namespace App\Http\Repositories\Shift;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Shift\ShiftRepositoryInterface;
use App\Models\Shift;

class ShiftRepository extends BaseRepository implements ShiftRepositoryInterface
{
    public function __construct(Shift $model)
    {
        parent::__construct($model);
    }
}