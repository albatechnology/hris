<?php

namespace App\Http\Repositories;

use App\Interfaces\Repositories\BankRepositoryInterface;
use App\Models\Bank;

class BankRepository extends BaseRepository implements BankRepositoryInterface
{
    public function __construct(Bank $model)
    {
        parent::__construct($model);
    }
}
