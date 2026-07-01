<?php

namespace App\Http\Repositories\Payroll;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Payroll\PayrollComponentRepositoryInterface;
use App\Models\RunPayroll;

class PayrollComponentRepository extends BaseRepository implements PayrollComponentRepositoryInterface
{
    public function __construct(RunPayroll $model)
    {
        parent::__construct($model);
    }
}
