<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserPayrollInfo\SalaryStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\BankInformationStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\TaskConfigurationStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\BpjsConfigurationStoreRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;

class UserPayrollInfoController extends BaseController
{
    public function salary(User $user, SalaryStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo'));
    }

    public function bankInformation(User $user, BankInformationStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo'));
    }

    public function taxConfiguration(User $user, TaskConfigurationStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo'));
    }

    public function bpjsConfiguration(User $user, BpjsConfigurationStoreRequest $request)
    {
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo'));
    }
}
