<?php

namespace App\Http\Services\SupervisorType;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\SupervisorType\SupervisorTypeRepositoryInterface;
use App\Interfaces\Services\SupervisorType\SupervisorTypeServiceInterface;
use App\Models\SupervisorType;

class SupervisorTypeService extends BaseService implements SupervisorTypeServiceInterface
{
    public function __construct(protected SupervisorTypeRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}