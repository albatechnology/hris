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
        // client_id is for SMART
        $payrollSetting = PayrollSetting::tenanted()
            ->where('company_id', $request->filter['company_id'])
            ->when($request->filter['client_id'] ?? null, fn($q) => $q->where('client_id', $request->filter['client_id']))
            ->firstOrFail();

        return new DefaultResource($payrollSetting);
    }

    public function update(PayrollSetting $payrollSetting, UpdateRequest $request)
    {
        // client_id is for SMART
        $payrollSetting = PayrollSetting::tenanted()
            ->where('company_id', $request->company_id)
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->firstOrFail();

        $payrollSetting->update($request->validated());

        return (new DefaultResource($payrollSetting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
