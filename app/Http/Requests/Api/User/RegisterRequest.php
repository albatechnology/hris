<?php

namespace App\Http\Requests\Api\User;

use App\Enums\BloodType;
use App\Enums\CostCenterCategory;
use App\Enums\CurrencyCode;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\JaminanPensiunCost;
use App\Enums\JhtCost;
use App\Enums\MaritalStatus;
use App\Enums\NppBpjsKetenagakerjaan;
use App\Enums\OvertimeSetting;
use App\Enums\PaymentSchedule;
use App\Enums\ProrateSetting;
use App\Enums\PtkpStatus;
use App\Enums\Religion;
use App\Enums\SalaryType;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Overtime;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'group_id' => 'nullable|exists:groups,id',
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'overtime_id' => ['nullable', new CompanyTenantedRule(Overtime::class, 'Overtime data not found')],
            'approval_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => 'nullable',
            'phone' => 'nullable',
            'gender' => ['required', Rule::enum(Gender::class)],
            'join_date' => 'nullable|date',
            'sign_date' => 'nullable|date',

            'no_ktp' => 'required|string',
            'kk_no' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'address' => 'required|string',
            'address_ktp' => 'required|string',
            // 'job_position' => 'required|string',
            // 'job_level' => 'required|string',
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'passport_no' => 'nullable|string',
            'passport_expired' => 'nullable|date',
            'birth_place' => 'required|string',
            'birthdate' => 'required|date',
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'blood_type' => ['required', Rule::enum(BloodType::class)],
            'religion' => ['required', Rule::enum(Religion::class)],
            // 'batik_size' => 'required|string',
            // 'tshirt_size' => 'required|string',

            'basic_salary' => 'required|numeric',
            'salary_type' => ['required', Rule::enum(SalaryType::class)],
            'payment_schedule' => ['required', Rule::enum(PaymentSchedule::class)],
            'prorate_setting' => ['required', Rule::enum(ProrateSetting::class)],
            'overtime_setting' => ['required', Rule::enum(OvertimeSetting::class)],
            'cost_center_category' => ['required', Rule::enum(CostCenterCategory::class)],
            'currency' => ['required', Rule::enum(CurrencyCode::class)],
            'bank_name' => 'nullable|string',
            'bank_account_no' => 'nullable|string',
            'bank_account_holder' => 'nullable|string',
            'npwp' => 'nullable|string',
            'ptkp_status' => ['required', Rule::enum(PtkpStatus::class)],
            'tax_method' => ['required', Rule::enum(TaxMethod::class)],
            'tax_salary' => ['required', Rule::enum(TaxSalary::class)],
            'taxable_date' => 'nullable|date',
            'employee_tax_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'beginning_netto' => 'nullable|string',
            'pph21_paid' => 'nullable|integer',
            'bpjs_ketenagakerjaan_no' => 'nullable|string',
            'npp_bpjs_ketenagakerjaan' => ['required', Rule::enum(NppBpjsKetenagakerjaan::class)],
            'bpjs_ketenagakerjaan_date' => 'required|date',
            'bpjs_kesehatan_no' => 'nullable|string',
            'bpjs_kesehatan_family_no' => 'nullable|string',
            'bpjs_kesehatan_date' => 'nullable|date',
            'bpjs_kesehatan_cost' => ['required', Rule::enum(NppBpjsKetenagakerjaan::class)],
            'jht_cost' => ['required', Rule::enum(JhtCost::class)],
            'jaminan_pensiun_cost' => ['required', Rule::enum(JaminanPensiunCost::class)],
            'jaminan_pensiun_date' => 'nullable|date',

            'positions' => 'nullable|array',
            'positions.*.position_id' => 'required|exists:positions,id',
            'positions.*.department_id' => 'required|exists:departments,id',

            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|exists:roles,id',

            'company_ids' => 'nullable|array',
            'company_ids.*' => ['required', new CompanyTenantedRule()],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
        ];
    }
}
