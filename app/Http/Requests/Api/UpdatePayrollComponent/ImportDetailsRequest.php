<?php

namespace App\Http\Requests\Api\UpdatePayrollComponent;

use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportDetailsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isFileRequired = "required";
        if ($this->segment('4') != '') {
            $isFileRequired = "nullable";
        }

        return [
            // 'update_payroll_component_id' => ['nullable', new CompanyTenantedRule(UpdatePayrollComponent::class, 'Update Payroll Component not found')],
            'company_id' => ['required', new CompanyTenantedRule()],
            'file' => [$isFileRequired, 'file', 'mimes:csv,xlsx'],
        ];
    }
}
