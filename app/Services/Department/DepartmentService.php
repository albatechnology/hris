<?php

namespace App\Services\Department;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Department\DepartmentRepositoryInterface;
use App\Interfaces\Services\Department\DepartmentServiceInterface;

class DepartmentService extends BaseService implements DepartmentServiceInterface
{
    public function __construct(protected DepartmentRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}