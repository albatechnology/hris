<?php

namespace App\Http\Requests\Api\UpdatePayrollComponent;

use App\Enums\UpdatePayrollComponentType;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    

    protected function prepareForValidation()
    {
        $branchId = $this->branch_id ?? null;
        $companyId = $this->company_id ?? null;
        if ($branchId) {
            $companyId = Branch::tenanted()->where('id', $branchId)->firstOrFail(['company_id'])->company_id;
        }

        $this->merge([
            'branch_id' => $branchId,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => Rule::requiredIf(config('app.name') === "Syntegra"),
            'company_id' => ['required', new CompanyTenantedRule()],
            'type' => ['required', Rule::enum(UpdatePayrollComponentType::class)],
            'description' => 'nullable|string',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date',
            'backpay_date' => 'nullable|date',

            'details' => 'required|array',
            'details.*.user_id' => 'required_with:details|integer|exists:users,id',
            'details.*.payroll_component_id' => 'required_with:details|integer|exists:payroll_components,id',
            'details.*.current_amount' => 'nullable|numeric',
            'details.*.new_amount' => 'nullable|numeric',
        ];
    }
}
