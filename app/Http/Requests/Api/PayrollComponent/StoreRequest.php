<?php

namespace App\Http\Requests\Api\PayrollComponent;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentDailyMaximumAmountType;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentSetting;
use App\Enums\PayrollComponentType;
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
            'is_taxable' => $this->toBoolean($this->is_taxable),
            'is_monthly_prorate' => $this->toBoolean($this->is_monthly_prorate),
            'is_daily_default' => $this->toBoolean($this->is_daily_default),
            'is_one_time_bonus' => $this->toBoolean($this->is_one_time_bonus),
            'is_include_backpay' => $this->toBoolean($this->is_include_backpay),
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
            'name' => 'required|string',
            'type' => ['required', Rule::enum(PayrollComponentType::class)],
            'category' => ['nullable', Rule::enum(PayrollComponentCategory::class)],
            'setting' => ['nullable', Rule::enum(PayrollComponentSetting::class)],
            'amount' => 'required|numeric',
            'is_taxable' => 'required|boolean',
            'period_type' => ['required', Rule::enum(PayrollComponentPeriodType::class)],
            'is_monthly_prorate' => 'nullable|boolean',
            'is_daily_default' => 'nullable|boolean',
            'daily_maximum_amount_type' => ['nullable', Rule::enum(PayrollComponentDailyMaximumAmountType::class)],
            'daily_maximum_amount' => 'required|numeric',
            'is_one_time_bonus' => 'nullable|boolean',
            'is_include_backpay' => 'nullable|boolean',

            'includes' => 'nullable|array',
            'includes.*.payroll_component_id' => 'required_with:includes|string',
            'includes.*.type' => 'required_with:includes|string',

            'formulas' => 'nullable|array',
            'formulas.*.component' => 'required_with:formulas|string',
            'formulas.*.value' => 'required_with:formulas|string',
            'formulas.*.amount' => 'required_without:formulas.*.child',
            'formulas.*.child' => 'required_without:formulas.*.amount|array',
        ];
    }
}
