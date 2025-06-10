<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollSetting\IndexRequest;
use App\Http\Requests\Api\PayrollSetting\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\PayrollSetting;
use Illuminate\Http\Response;

class PayrollSettingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:payroll_setting_read', ['only' => 'index']);
        $this->middleware('permission:payroll_setting_edit', ['only' => 'update']);
    }

    public function index(IndexRequest $request)
    {
        $payrollSetting = PayrollSetting::tenanted()
            ->where('company_id', $request->filter['company_id'])
            ->when($request->filter['branch_id'] ?? null, fn($q) => $q->where('branch_id', $request->filter['branch_id']))
            ->firstOrFail();

        return new DefaultResource($payrollSetting);
    }

    public function update(PayrollSetting $payrollSetting, UpdateRequest $request)
    {
        $payrollSetting = PayrollSetting::tenanted()
            ->where('company_id', $request->company_id)
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->firstOrFail();

        $payrollSetting->update($request->validated());

        return (new DefaultResource($payrollSetting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
