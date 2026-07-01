<?php

namespace App\Http\Repositories\Group;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Group\GroupRepositoryInterface;
use App\Models\Group;

class GroupRepository extends BaseRepository implements GroupRepositoryInterface
{
    public function __construct(Group $model)
    {
        parent::__construct($model);
    }
}
