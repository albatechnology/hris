<?php

namespace App\Http\Resources\Overtime;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            ...parent::toArray($request),
            'overtime_roundings' => OvertimeRoundingResource::collection($this->overtimeRoundings),
            'overtime_multipliers' => OvertimeMultiplierResource::collection($this->overtimeMultipliers),
            'overtime_allowances' => OvertimeAllowanceResource::collection($this->overtimeAllowances),
            'formulas' => $this->formulas->load('formulaComponents', 'child'),
        ];
    }
}
