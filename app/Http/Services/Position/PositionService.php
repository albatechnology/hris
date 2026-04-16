<?php

namespace App\Http\Services\Position;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Position\PositionRepositoryInterface;
use App\Interfaces\Services\Position\PositionServiceInterface;

class PositionService extends BaseService implements PositionServiceInterface
{
    public function __construct(protected PositionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
