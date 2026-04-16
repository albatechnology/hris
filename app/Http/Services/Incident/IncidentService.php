<?php

namespace App\Http\Services\Incident;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Incident\IncidentRepositoryInterface;
use App\Interfaces\Services\Incident\IncidentServiceInterface;

class IncidentService extends BaseService implements IncidentServiceInterface
{
    public function __construct(protected IncidentRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
