<?php

namespace App\Http\Resources\Formula;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormulaResource  extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return ['formulas' => $this->formulas->load('formulaComponents', 'child')];
    }
}
