<?php

namespace App\Interfaces\Services\Payroll;

use App\Http\DTO\Payroll\RunPayrollDTO;
use App\Interfaces\Services\BaseServiceInterface;
use App\Models\RunPayroll;

interface RunPayrollServiceInterface extends BaseServiceInterface
{
    public function store(RunPayrollDTO $dto): RunPayroll;
}
