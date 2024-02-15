<?php

namespace App\Http\Requests\Api\Overtime;

use App\Enums\RateType;
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

            'overtime_roundings' => 'required_if:is_rounding,true|array',
            'overtime_roundings.*.start_minute' => 'required_with:overtime_roundings|integer',
            'overtime_roundings.*.end_minute' => 'required_with:overtime_roundings|integer|gt:overtime_roundings.*.start_minute',
            'overtime_roundings.*.rounded' => 'required_with:overtime_roundings|integer',

            'overtime_multipliers' => 'required_without:compensation_rate_per_day|array',
            'overtime_multipliers.*.is_weekday' => 'required_with:overtime_multipliers|boolean',
            'overtime_multipliers.*.start_hour' => 'required_with:overtime_multipliers|integer',
            'overtime_multipliers.*.end_hour' => 'required_with:overtime_multipliers|integer|gt:overtime_multipliers.*.start_hour',
            'overtime_multipliers.*.multiply' => 'required_with:overtime_multipliers|integer',

            'overtime_allowances' => 'required_if:rate_type,allowances|array',
            'overtime_allowances.*.amount' => 'required_with:overtime_allowances||numeric',

            'overtime_formulas' => 'nullable|array',
            'overtime_formulas.*.component' => 'required_with:overtime_formulas|string',
            'overtime_formulas.*.value' => 'required_with:overtime_formulas|string',
            'overtime_formulas.*.amount' => 'required_without:overtime_formulas.*.child',
            'overtime_formulas.*.child' => 'required_without:overtime_formulas.*.amount|array',
        ];
    }
}
