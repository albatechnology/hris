<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use Illuminate\Foundation\Http\FormRequest;

class PayrollComponentStoreRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payroll_components' => 'nullable|array',
            'payroll_components.*.payroll_component_id' => 'required_with:payroll_components|exists:payroll_components,id|distinct',
            'payroll_components.*.amount' => 'nullable|numeric',

        ];
    }
}
