<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserPayrollInfo\SalaryStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\BankInformationStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\TaskConfigurationStoreRequest;
use App\Http\Requests\Api\UserPayrollInfo\BpjsConfigurationStoreRequest;
use App\Http\Resources\User\UserPayrollInfoResource;

class UserPayrollInfoController extends BaseController
{
    public function salary(User $user, SalaryStoreRequest $request)
    {
        if ($user->salary) {
            $user->salary->update($request->validated());
        } else {
            $user->salary()->create($request->validated());
        }
        return new UserPayrollInfoResource($user->load('salary'));
    }

    public function bankInformation(User $user, BankInformationStoreRequest $request)
    {
        if ($user->bankInformation) {
            $user->bankInformation->update($request->validated());
        } else {
            $user->bankInformation()->create($request->validated());
        }
        return new UserPayrollInfoResource($user->load('bankInformation'));
    }

    public function taxConfiguration(User $user, TaskConfigurationStoreRequest $request)
    {
        if ($user->taxConfiguration) {
            $user->taxConfiguration->update($request->validated());
        } else {
            $user->taxConfiguration()->create($request->validated());
        }
        return new UserPayrollInfoResource($user->load('taxConfiguration'));
    }

    public function bpjsConfiguration(User $user, BpjsConfigurationStoreRequest $request)
    {
        if ($user->bpjsConfiguration) {
            $user->bpjsConfiguration->update($request->validated());
        } else {
            $user->bpjsConfiguration()->create($request->validated());
        }
        return new UserPayrollInfoResource($user->load('bpjsConfiguration'));
    }
}
