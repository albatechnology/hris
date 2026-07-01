<?php

namespace App\Services\Division;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Division\DivisionRepositoryInterface;
use App\Interfaces\Services\Division\DivisionServiceInterface;

class DivisionService extends BaseService implements DivisionServiceInterface
{
    public function __construct(protected DivisionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}