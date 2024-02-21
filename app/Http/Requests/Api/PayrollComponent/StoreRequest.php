<?php

namespace App\Http\Requests\Api\PayrollComponent;

use App\Enums\PayrollComponentDailyMaximumAmountType;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
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
            'company_id' => ['required', new CompanyTenantedRule()],
            'name' => 'required|string',
            'type' => ['required', Rule::enum(PayrollComponentType::class)],
            'amount' => 'required|numeric',
            'is_taxable' => 'required|boolean',
            'period_type' => ['required', Rule::enum(PayrollComponentPeriodType::class)],
            'is_monthly_prorate' => 'required|boolean',
            'is_daily_default' => 'required|boolean',
            'daily_maximum_amount_type' => ['required', Rule::enum(PayrollComponentDailyMaximumAmountType::class)],
            'daily_maximum_amount' => 'required|numeric',
            'is_one_time_bonus' => 'required|boolean',

            'formulas' => 'nullable|array',
            'formulas.*.component' => 'required_with:formulas|string',
            'formulas.*.value' => 'required_with:formulas|string',
            'formulas.*.amount' => 'required_without:formulas.*.child',
            'formulas.*.child' => 'required_without:formulas.*.amount|array',
        ];
    }
}
