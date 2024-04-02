<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollSchedule\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:payroll_schedule_read', ['only' => 'index']);
        $this->middleware('permission:payroll_schedule_edit', ['only' => 'update']);
    }

    public function index(Request $request)
    {
        return new PayrollScheduleResource($request);
    }

    public function update(PayrollSetting $payrollSetting, UpdateRequest $request)
    {
        $payrollSetting->update($request->validated());

        return (new PayrollScheduleResource($payrollSetting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
