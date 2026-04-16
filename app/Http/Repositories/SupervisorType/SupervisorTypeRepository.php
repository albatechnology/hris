<?php

namespace App\Http\Repositories\SupervisorType;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\SupervisorType\SupervisorTypeRepositoryInterface;
use App\Models\SupervisorType;

class SupervisorTypeRepository extends BaseRepository implements SupervisorTypeRepositoryInterface
{
    public function __construct(SupervisorType $model)
    {
        parent::__construct($model);
    }
}