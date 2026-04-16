<?php

namespace App\Repositories\Department;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Department\DepartmentRepositoryInterface;
use App\Models\Department;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }
}