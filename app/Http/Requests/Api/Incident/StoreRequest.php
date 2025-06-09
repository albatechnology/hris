<?php

namespace App\Http\Requests\Api\Incident;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $companyId = $this->company_id ?? null;
        if ($this->branch_id) {
            $companyId = Branch::tenanted()->where('id', $this->branch_id)->firstOrFail(['company_id'])->company_id;
        }

        $this->merge([
            'company_id' => $companyId
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
            'company_id' => ['required', new CompanyTenantedRule()],
            'branch_id' => [Rule::requiredIf(config('app.name') == "Syntegra"), new CompanyTenantedRule(Branch::class)],
            'incident_type_id' => 'required|exists:incident_types,id',
            'description' => 'required|string',
            'file' => 'nullable|array',
            'file.*' => 'required|mimes:' . config('app.file_mimes_types'),
        ];
    }
}
