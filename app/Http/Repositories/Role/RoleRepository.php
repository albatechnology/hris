<?php

namespace App\Http\Repositories\Role;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Role\RoleRepositoryInterface;
use App\Models\Role;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }
}