<?php

namespace App\Repositories\Division;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Division\DivisionRepositoryInterface;
use App\Models\Division;
use Closure;
use Illuminate\Database\Eloquent\Model;

class DivisionRepository extends BaseRepository implements DivisionRepositoryInterface
{
    public function __construct(Division $model)
    {
        parent::__construct($model);
    }
}