<?php

namespace App\Http\Repositories\Payroll;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Payroll\RunPayrollRepositoryInterface;
use App\Models\RunPayroll;

class RunPayrollRepository extends BaseRepository implements RunPayrollRepositoryInterface
{
    public function __construct(RunPayroll $model)
    {
        parent::__construct($model);
    }
}
