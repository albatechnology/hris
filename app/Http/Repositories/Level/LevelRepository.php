<?php

namespace App\Http\Repositories\Level;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Level\LevelRepositoryInterface;
use App\Models\Level;


class LevelRepository extends BaseRepository implements LevelRepositoryInterface
{
    public function __construct(Level $model)
    {
        parent::__construct($model);
    }
}
