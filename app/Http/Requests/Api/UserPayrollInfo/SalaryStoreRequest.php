<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Enums\CostCenterCategory;
use App\Enums\CurrencyCode;
use App\Enums\OvertimeSetting;
use App\Enums\PaymentSchedule;
use App\Enums\ProrateSetting;
use App\Enums\SalaryType;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryStoreRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_ignore_alpa' => $this->toBoolean($this->is_ignore_alpa) ?? false
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
            'basic_salary' => 'required|integer',
            'total_working_days' => 'required|integer',
            'salary_type' => ['required', Rule::enum(SalaryType::class)],
            'payment_schedule' => ['required', Rule::enum(PaymentSchedule::class)],
            'prorate_setting' => ['nullable', Rule::enum(ProrateSetting::class)],
            'overtime_setting' => ['required', Rule::enum(OvertimeSetting::class)],
            'cost_center_category' => ['nullable', Rule::enum(CostCenterCategory::class)],
            'currency' => ['required', Rule::enum(CurrencyCode::class)],
            'epf_no' => 'nullable|string',
            'tabungan_haji_no' => 'nullable|string',
            'is_ignore_alpa' => 'required|boolean',
        ];
    }
}
