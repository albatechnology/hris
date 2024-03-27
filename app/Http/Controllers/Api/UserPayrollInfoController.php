<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserPayrollInfo\SalaryStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\BankInformationStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\BpjsConfigurationStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\PayrollComponentStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\TaxConfigurationStoreRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;

class UserPayrollInfoController extends BaseController
{
    public function salary(User $user, SalaryStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function bankInformation(User $user, BankInformationStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function taxConfiguration(User $user, TaxConfigurationStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function bpjsConfiguration(User $user, BpjsConfigurationStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function payrollComponent(User $user, PayrollComponentStoreRequest $request)
    {
        $user->payrollInfo->components()->delete();
        if ($request->payroll_components) $user->payrollInfo->components()->createMany($request->payroll_components);
        return new UserResource($user->load('payrollInfo.components'));
    }
}
