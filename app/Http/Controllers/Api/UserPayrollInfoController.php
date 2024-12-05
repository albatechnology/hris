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
    public function salary(int $userId, SalaryStoreRequest $request)
    {
        $user = User::findTenanted($userId);
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function bankInformation(int $userId, BankInformationStoreRequest $request)
    {
        $user = User::findTenanted($userId);
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function taxConfiguration(int $userId, TaxConfigurationStoreRequest $request)
    {
        $user = User::findTenanted($userId);
        $user->payrollInfo->update($request->validated());
        return new UserResource($user->load('payrollInfo.components'));
    }

    public function bpjsConfiguration(int $userId, BpjsConfigurationStoreRequest $request)
    {
        $user = User::findTenanted($userId);
        $user->userBpjs()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return new UserResource($user->load('userBpjs'));
    }

    public function payrollComponent(int $userId, PayrollComponentStoreRequest $request)
    {
        $user = User::findTenanted($userId);
        $user->payrollInfo->components()->delete();
        if ($request->payroll_components) $user->payrollInfo->components()->createMany($request->payroll_components);
        return new UserResource($user->load('payrollInfo.components'));
    }
}
