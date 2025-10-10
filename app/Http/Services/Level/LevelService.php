<?php

namespace App\Http\Services\Level;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Level\LevelRepositoryInterface;
use App\Interfaces\Services\Level\LevelServiceInterface;


class LevelService extends BaseService implements LevelServiceInterface
{
    public function __construct(protected LevelRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
