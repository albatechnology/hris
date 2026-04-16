<?php

namespace App\Http\Services\Group;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Group\GroupRepositoryInterface;
use App\Interfaces\Services\Group\GroupServiceInterface;

class GroupService extends BaseService implements GroupServiceInterface
{
    public function __construct(protected GroupRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
