<?php

namespace App\Http\Requests\Api\PayrollSetting;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
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
        $branchId = $this->branch_id ?? null;
        $companyId = $this->company_id ?? null;
        if ($branchId) {
            $companyId = Branch::tenanted()->where('id', $branchId)->firstOrFail(['company_id'])->company_id;
        }

        $this->merge([
            'branch_id' => $branchId,
            'company_id' => $companyId,
            'is_attendance_pay_last_month' => $this->toBoolean($this->is_attendance_pay_last_month ?? 0),
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
            // 'cut_off_date' => 'required|date_format:d',
            'cut_off_attendance_start_date' => 'required|date_format:d',
            'cut_off_attendance_end_date' => 'required|date_format:d',
            'is_attendance_pay_last_month' => 'required|boolean',
            'payroll_start_date' => 'required|date_format:d',
            'payroll_end_date' => 'required|date_format:d',
            // 'default_employee_tax_setting' => ['required', Rule::enum(TaxMethod::class)],
            // 'default_employee_salary_tax_setting' => ['required', Rule::enum(TaxSalary::class)],
            // 'default_oas_setting' => ['required', Rule::enum(JhtCost::class)],
        ];
    }
}
