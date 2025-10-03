<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollProrate\UpdateRequest;
use App\Http\Resources\PayrollProrate\PayrollProrateResource;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;

class PayrollProrateController extends BaseController
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

        $payrollSetting = PayrollSetting::select('id','company_id','prorate_setting','prorate_custom_working_day','prorate_national_holiday_as_working_day')->tenanted()->where('company_id', $request->filter['company_id'])->firstOrFail();
        return new PayrollProrateResource($payrollSetting);
    }

    public function update(UpdateRequest $request, int $id)
    {
        $payrollSetting = PayrollSetting::tenanted()->where('id', $id)->firstOrFail();
        $payrollSetting->update($request->validated());

        return $this->updatedResponse();
    }
}
