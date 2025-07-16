<?php

namespace App\Http\Requests\Overtime;

use App\Enums\RateType;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
     /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'compensation_rate_per_day' => $this->compensation_rate_per_day ?? 0,
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
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'name' => 'required|string',
            'is_rounding' => 'required|boolean',
            'compensation_rate_per_day' => 'nullable|numeric',
            'rate_type' => ['nullable', Rule::enum(RateType::class)],
            'rate_amount' => 'required|numeric',

        ];
    }
}
