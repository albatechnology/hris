<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Enums\CostCenterCategory;
use App\Enums\CurrencyCode;
use App\Enums\OvertimeSetting;
use App\Enums\PaymentSchedule;
use App\Enums\ProrateSetting;
use App\Enums\SalaryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryStoreRequest extends FormRequest
{


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
        ];
    }
}
