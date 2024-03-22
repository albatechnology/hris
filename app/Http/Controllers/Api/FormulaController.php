<?php

namespace App\Http\Controllers\Api;

use App\Enums\FormulaAmountType;
use App\Enums\FormulaComponentEnum;

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
}
