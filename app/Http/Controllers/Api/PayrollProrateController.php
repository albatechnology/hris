<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PayrollProrate\UpdateRequest;
use App\Http\Resources\PayrollProrate\PayrollProrateResource;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollProrateController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:payroll_prorate_read', ['only' => 'index']);
        $this->middleware('permission:payroll_prorate_edit', ['only' => 'update']);
    }

    public function index(Request $request)
    {
        return new PayrollProrateResource($request);
    }

    public function update(PayrollSetting $payrollSetting, UpdateRequest $request)
    {
        $payrollSetting->update($request->validated());

        return (new PayrollProrateResource($payrollSetting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
