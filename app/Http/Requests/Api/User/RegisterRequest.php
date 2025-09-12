<?php

namespace App\Http\Requests\Api\User;

use App\Enums\BloodType;
use App\Enums\BpjsKesehatanFamilyNo;
use App\Enums\CostCenterCategory;
use App\Enums\CurrencyCode;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\JaminanPensiunCost;
use App\Enums\MaritalStatus;
use App\Enums\NppBpjsKetenagakerjaan;
use App\Enums\OvertimeSetting;
use App\Enums\PaidBy;
use App\Enums\PaymentSchedule;
use App\Enums\ProrateSetting;
use App\Enums\PtkpStatus;
use App\Enums\Religion;
use App\Enums\SalaryType;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Enums\UserType;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Overtime;
use App\Models\Schedule;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $email = $this->email;
        if (!$email) {
            if ($this->nik) {
                $email = $this->nik . '@gmail.com';
            } else {
                $email = str_replace(' ', '', $this->name) . time() . '@gmail.com';
            }
        }

        $emailVerifiedAt = null;
        if ($this->password) $emailVerifiedAt = date('Y-m-d H:i:s');
        if ($this->email_verified_at) $emailVerifiedAt = date('Y-m-d H:i:s', strtotime($this->email_verified_at));

        $user = auth()->user();
        $data = [
            'group_id' => $this->group_id ?? $user->group_id,
            'company_id' => $this->company_id ?? $user->company_id,
            'branch_id' => $this->branch_id ?? $user->branch_id,
            'email' => $email,
            'bank_id' => $this->bank_id ?? Bank::where('company_id', $this->company_id)->first(['id'])?->id,
            'month' => $this->month ?? date('m'),
            'year' => $this->year ?? date('Y'),
            'email_verified_at' => $emailVerifiedAt,
            'bpjs_kesehatan_family_no' => $this->bpjs_kesehatan_family_no ?? 0,
            'currency' => $this->currency ?? CurrencyCode::IDR->value,
        ];

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_id' => 'required|exists:groups,id',
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'overtime_id' => ['nullable', new CompanyTenantedRule(Overtime::class, 'Overtime data not found')],
            // 'approval_id' => 'nullable|exists:users,id',
            // 'parent_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            // 'last_name' => 'nullable|string',
            'email' => 'required|email|unique:users,email',
            'work_email' => 'nullable|email',
            'email_verified_at' => 'nullable|date_format:Y-m-d H:i:s',
            'password' => 'nullable|string',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => 'nullable',
            'phone' => 'nullable',
            'gender' => ['required', Rule::enum(Gender::class)],
            'join_date' => 'nullable|date',
            'sign_date' => 'nullable|date',
            'end_contract_date' => 'nullable|date',
            'resign_date' => 'nullable|date',
            'photo_profile' => 'nullable|mimes:' . config('app.file_mimes_types'),

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
            'religion' => ['nullable', Rule::enum(Religion::class)],
            // 'batik_size' => 'required|string',
            // 'tshirt_size' => 'required|string',

            'bank_id' => ['required', new CompanyTenantedRule(Bank::class, 'Bank not found')],
            'basic_salary' => 'required|numeric',
            'salary_type' => ['nullable', Rule::enum(SalaryType::class)],
            'payment_schedule' => ['nullable', Rule::enum(PaymentSchedule::class)],
            'prorate_setting' => ['nullable', Rule::enum(ProrateSetting::class)],
            'overtime_setting' => ['required', Rule::enum(OvertimeSetting::class)],
            'cost_center_category' => ['nullable', Rule::enum(CostCenterCategory::class)],
            'currency' => ['nullable', Rule::enum(CurrencyCode::class)],
            'bank_name' => 'nullable|string',
            'bank_account_no' => 'nullable|string',
            'bank_account_holder' => 'nullable|string',
            'secondary_bank_name' => 'nullable|string',
            'secondary_bank_account_no' => 'nullable|string',
            'secondary_bank_account_holder' => 'nullable|string',
            'npwp' => 'nullable|string',
            'ptkp_status' => ['nullable', Rule::enum(PtkpStatus::class)],
            'tax_method' => ['nullable', Rule::enum(TaxMethod::class)],
            'tax_salary' => ['nullable', Rule::enum(TaxSalary::class)],
            'taxable_date' => 'nullable|date',
            'employee_tax_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'beginning_netto' => 'nullable|string',
            'pph21_paid' => 'nullable|integer',
            'bpjs_ketenagakerjaan_no' => 'nullable|string',
            'npp_bpjs_ketenagakerjaan' => ['nullable', Rule::enum(NppBpjsKetenagakerjaan::class)],
            'bpjs_ketenagakerjaan_date' => 'nullable|date',
            'bpjs_kesehatan_no' => 'nullable|string',
            'bpjs_kesehatan_family_no' => ['required', Rule::enum(BpjsKesehatanFamilyNo::class)],
            'bpjs_kesehatan_date' => 'nullable|date',
            'bpjs_kesehatan_cost' => ['nullable', Rule::enum(PaidBy::class)],
            'jht_cost' => ['nullable', Rule::enum(PaidBy::class)],
            'jaminan_pensiun_cost' => ['nullable', Rule::enum(JaminanPensiunCost::class)],
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
