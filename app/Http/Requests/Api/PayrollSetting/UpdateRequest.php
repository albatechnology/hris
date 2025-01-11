<?php

namespace App\Http\Requests\Api\PayrollSetting;

use App\Enums\TaxMethod;
use App\Enums\JhtCost;
use App\Enums\TaxSalary;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'cut_off_date' => 'required|date_format:d',
            'cut_off_attendance_start_date' => 'required|date_format:d',
            'cut_off_attendance_end_date' => 'required|date_format:d',
            'default_employee_tax_setting' => ['required', Rule::enum(TaxMethod::class)],
            'default_employee_salary_tax_setting' => ['required', Rule::enum(TaxSalary::class)],
            'default_oas_setting' => ['required', Rule::enum(JhtCost::class)],
        ];
    }
}
