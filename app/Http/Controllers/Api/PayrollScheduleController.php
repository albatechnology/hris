<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollSchedule\UpdateRequest;
use App\Http\Resources\PayrollSchedule\PayrollScheduleResource;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:payroll_setting_read', ['only' => 'index']);
        $this->middleware('permission:payroll_setting_edit', ['only' => 'update']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'filter.company_id' => 'required',
        ]);

        $payrollSetting = PayrollSetting::tenanted()->where('company_id', $request->filter['company_id'])->firstOrFail();
        return new PayrollScheduleResource($payrollSetting);
    }

    public function update(UpdateRequest $request)
    {
        $payrollSetting = PayrollSetting::tenanted()->where('company_id', $request->company_id)->firstOrFail();
        $payrollSetting->update($request->validated());

        return (new PayrollScheduleResource($payrollSetting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
