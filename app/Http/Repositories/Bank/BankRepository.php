<?php

namespace App\Http\Repositories\Bank;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Bank\BankRepositoryInterface;
use App\Models\Bank;

class BankRepository extends BaseRepository implements BankRepositoryInterface
{
    public function __construct(Bank $model)
    {
        parent::__construct($model);
    }
}
