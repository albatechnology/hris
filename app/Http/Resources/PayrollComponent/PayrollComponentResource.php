<?php

namespace App\Http\Resources\PayrollComponent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollComponentResource extends JsonResource
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
            'includes' => $this->includes->load('includedPayrollComponent'),
            'formulas' => $this->formulas->load('formulaComponents', 'child'),
        ];
    }
}
