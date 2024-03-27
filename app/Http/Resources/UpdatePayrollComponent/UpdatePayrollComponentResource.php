<?php

namespace App\Http\Resources\UpdatePayrollComponent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdatePayrollComponentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $isActive = $this->end_date ? now() >= $this->effective_date && now() < $this->end_date : $this->effective_date <= now();
        $totalEmployee = $this->details()->groupBy('payroll_component_id')->count();

        return [
            ...parent::toArray($request),
            'is_active' => $isActive,
            'total_employee' => $totalEmployee,
        ];
    }
}
