<?php

namespace App\Http\Requests\Api\Overtime;

use App\Enums\FormulaComponentEnum;
use App\Enums\RateType;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'name' => 'required|string',
            'is_rounding' => 'required|boolean',
            'compensation_rate_per_day' => 'nullable|numeric',
            'rate_type' => ['nullable', Rule::enum(RateType::class)],
            'rate_amount' => 'required|numeric',

            'overtime_roundings' => 'required_if:is_rounding,true|array',
            'overtime_roundings.*.start_minute' => [
                'required_with:overtime_roundings',
                'integer',
                function ($attribute, int $value, \Closure $fail) {
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
                function ($attribute, int $value, \Closure $fail) {
                    $index = explode('.', $attribute)[1];
                    if ($index > 0 && $value <= (int)$this->overtime_multipliers[$index - 1]['end_hour']) {
                        $fail($attribute . ' must be greater than ' . $this->overtime_multipliers[$index - 1]['end_hour']);
                    }
                }
            ],
            'overtime_multipliers.*.end_hour' => 'required_with:overtime_multipliers|integer|gt:overtime_multipliers.*.start_hour',
            'overtime_multipliers.*.multiply' => 'required_with:overtime_multipliers|integer',

            'overtime_allowances' => 'required_if:rate_type,allowances|array',
            'overtime_allowances.*.payroll_component_id' => 'required_with:overtime_allowances|exists:payroll_components,id|distinct',
            'overtime_allowances.*.amount' => 'required_with:overtime_allowances|numeric',

            'formulas' => 'required_if:rate_type,formula|array',
            'formulas.*.component' => ['required_with:formulas', Rule::enum(FormulaComponentEnum::class)],
            'formulas.*.value' => 'required_with:formulas|string',
            'formulas.*.amount' => 'required_without:formulas.*.child|string',
            'formulas.*.child' => 'required_without:formulas.*.amount|array',

            'formulas.*.child.*.component' => ['required_with:formulas.*.child', Rule::enum(FormulaComponentEnum::class)],
            'formulas.*.child.*.value' => 'required_with:formulas.*.child|string',
            'formulas.*.child.*.amount' => 'required_with:formulas.*.child|string',
        ];
    }
}
