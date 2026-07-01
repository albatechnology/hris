<?php

namespace App\Http\Resources\Overtime;

use App\Http\Resources\DefaultResource;
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
            'overtime_roundings' => DefaultResource::collection($this->overtimeRoundings),
            'overtime_multipliers' => DefaultResource::collection($this->overtimeMultipliers),
            'overtime_allowances' => OvertimeAllowanceResource::collection($this->overtimeAllowances),
            'formulas' => $this->formulas->load('formulaComponents', 'child'),
        ];
    }
}
