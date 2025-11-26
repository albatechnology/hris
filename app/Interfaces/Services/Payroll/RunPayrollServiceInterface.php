<?php

namespace App\Interfaces\Services\Payroll;

use App\Http\DTO\Payroll\RunPayrollDTO;
use App\Interfaces\Services\BaseServiceInterface;
use App\Models\RunPayroll;
use Illuminate\Http\JsonResponse;

interface RunPayrollServiceInterface extends BaseServiceInterface
{
    public function store(RunPayrollDTO $dto): RunPayroll | JsonResponse;
}
