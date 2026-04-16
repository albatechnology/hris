<?php

namespace App\Http\Repositories\Incident;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Incident\IncidentRepositoryInterface;
use App\Models\Incident;

class IncidentRepository extends BaseRepository implements IncidentRepositoryInterface
{
    public function __construct(Incident $model)
    {
        parent::__construct($model);
    }
}
