<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollSetting\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollSettingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        // $this->middleware('permission:payroll_setting_access', ['only' => ['restore']]);
        // $this->middleware('permission:payroll_setting_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:payroll_setting_edit', ['only' => 'update']);
    }

    public function index(Request $request)
    {
        $request->validate([
            'filter.company_id' => 'required',
        ]);

        $payrollSetting = PayrollSetting::tenanted()->where('company_id', $request->filter['company_id'])->firstOrFail();
        return new DefaultResource($payrollSetting);
    }

    public function update(PayrollSetting $payrollSetting, UpdateRequest $request)
    {
        $payrollSetting = PayrollSetting::where('company_id', $request->company_id)->firstOrFail();
        $payrollSetting->update($request->validated());

        return (new DefaultResource($payrollSetting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
