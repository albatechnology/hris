<?php

namespace App\Http\Controllers\Api;

use App\Enums\FormulaAmountType;
use App\Enums\FormulaComponentEnum;
use App\Models\Overtime;
use App\Models\User;
use App\Services\FormulaService;
use App\Services\OvertimeService;
use Illuminate\Http\Request;

class FormulaController extends BaseController
{
    public function components(string $formulaComponent)
    {
        $formulaComponent = FormulaComponentEnum::getValue($formulaComponent);
        return response()->json(['data' => $formulaComponent->getData()]);
    }

    public function amounts()
    {
        return response()->json(['data' => FormulaAmountType::getAvailableAmounts()]);
    }

    public function test(Request $request)
    {
        $user = auth()->user();
        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
        }

        $startPeriod = date('Y-m-d', strtotime($request->start_period));
        $endPeriod = date('Y-m-d', strtotime($request->end_period));

        $overtime = Overtime::findOrFail(9);
        $amount = 0;
        // $amount = FormulaService::calculate($user, $overtime, $overtime->formulas, $amount, $startPeriod, $endPeriod);
        $amount = OvertimeService::calculate($user, $startPeriod, $endPeriod);
        return $amount;
    }
}
