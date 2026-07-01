<?php

namespace App\Http\Repositories\Payroll;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Payroll\PayrollComponentRepositoryInterface;
use App\Models\PayrollComponent;

class PayrollComponentRepository extends BaseRepository implements PayrollComponentRepositoryInterface
{
    public function __construct(PayrollComponent $model)
    {
        parent::__construct($model);
    }
}
