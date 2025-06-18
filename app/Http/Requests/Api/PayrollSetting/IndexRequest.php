<?php

namespace App\Http\Requests\Api\PayrollSetting;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $branchId = $this->filter['branch_id'] ?? null;
        $companyId = $this->filter['company_id'] ?? null;
        if ($branchId) {
            $companyId = Branch::tenanted()->where('id', $branchId)->first(['company_id'])->company_id;
        }

        $this->merge([
            'filter' => [
                'branch_id' => $branchId,
                'company_id' => $companyId,
            ]
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
            'filter.branch_id' => Rule::requiredIf(config('app.name') === "Syntegra"),
            'filter.company_id' => ['required', new CompanyTenantedRule()],
        ];
    }
}
