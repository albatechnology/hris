<?php

namespace App\Http\Requests\Api\Overtime;

use App\Enums\RateType;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use RequestToBoolean;

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
        $this->merge([
            'company_id' => $this->company_id ? $this->company_id : auth('sanctum')->user()->company_id,
            'is_rounding' => $this->toBoolean($this->is_rounding),
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
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'name' => 'required|string',
            'is_rounding' => 'required|boolean',
            'compensation_rate_per_day' => 'nullable|numeric',
            'rate_type' => ['nullable', Rule::enum(RateType::class)],
            'rate_amount' => [Rule::requiredIf(in_array($this->rate_type, [RateType::AMOUNT->value, RateType::BASIC_SALARY->value])), 'numeric'],

            'overtime_roundings' => 'required_if:is_rounding,true|array',
            'overtime_roundings.*.start_minute' => [
                'required_with:overtime_roundings',
                'integer',
                function ($attribute, int $value, Closure $fail) {
                    $index = explode('.', $attribute)[1];
                    if ($index > 0 && $value <= (int)$this->overtime_roundings[$index - 1]['end_minute']) {
                        $fail($attribute . ' must be greater than ' . $this->overtime_roundings[$index - 1]['end_minute']);
                    }
                }
            ],
            'overtime_roundings.*.end_minute' => 'required_with:overtime_roundings|integer|gt:overtime_roundings.*.start_minute',
            'overtime_roundings.*.rounded' => 'required_with:overtime_roundings|integer',

            'overtime_multipliers' => 'required_without:compensation_rate_per_day|array',
            'overtime_multipliers.*.is_weekday' => 'required_with:overtime_multipliers|boolean',
            'overtime_multipliers.*.start_hour' => [
                'required_with:overtime_multipliers',
                'integer',
                function ($attribute, int $value, Closure $fail) {
                    $index = explode('.', $attribute)[1];
                    if ($index > 0 && $value <= (int)$this->overtime_multipliers[$index - 1]['end_hour']) {
                        $fail($attribute . ' must be greater than ' . $this->overtime_multipliers[$index - 1]['end_hour']);
                    }
                }
            ],
            'overtime_multipliers.*.end_hour' => 'required_with:overtime_multipliers|integer|gt:overtime_multipliers.*.start_hour',
            'overtime_multipliers.*.multiply' => 'required_with:overtime_multipliers|numeric',

            'overtime_allowances' => 'required_if:rate_type,allowances|array',
            'overtime_allowances.*.payroll_component_id' => 'required_with:overtime_allowances|exists:payroll_components,id|distinct',
            'overtime_allowances.*.amount' => 'required_with:overtime_allowances|numeric',

            'overtime_formulas' => 'nullable|array',
            'overtime_formulas.*.component' => 'required_with:overtime_formulas|string',
            'overtime_formulas.*.value' => 'required_with:overtime_formulas|string',
            'overtime_formulas.*.amount' => 'required_without:overtime_formulas.*.child',
            'overtime_formulas.*.child' => 'required_without:overtime_formulas.*.amount|array',
        ];
    }
}
