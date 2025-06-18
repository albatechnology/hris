<?php

namespace App\Http\Requests\Api\PayrollComponent;

use App\Enums\PayrollComponentCategory;
use App\Enums\PayrollComponentPeriodType;
use App\Enums\PayrollComponentType;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
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
            'is_taxable' => $this->toBoolean($this->is_taxable),
            'is_monthly_prorate' => $this->toBoolean($this->is_monthly_prorate),
            // 'is_daily_default' => $this->toBoolean($this->is_daily_default),
            // 'is_one_time_bonus' => $this->toBoolean($this->is_one_time_bonus),
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
            'branch_id' => Rule::requiredIf(config('app.name') === "Syntegra"),
            'company_id' => ['required', new CompanyTenantedRule()],
            'name' => 'required|string',
            'type' => ['required', Rule::enum(PayrollComponentType::class)],
            'category' => ['nullable', Rule::enum(PayrollComponentCategory::class)],
            // 'setting' => ['nullable', Rule::enum(PayrollComponentSetting::class)],
            'amount' => 'required_without:formulas|numeric',
            'is_taxable' => 'required|boolean',
            'period_type' => ['required', Rule::enum(PayrollComponentPeriodType::class)],
            'is_monthly_prorate' => 'nullable|boolean',
            // 'is_daily_default' => 'nullable|boolean',
            // 'daily_maximum_amount_type' => ['nullable', Rule::enum(PayrollComponentDailyMaximumAmountType::class)],
            // 'daily_maximum_amount' => 'nullable|numeric',
            // 'is_one_time_bonus' => 'nullable|boolean',
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
