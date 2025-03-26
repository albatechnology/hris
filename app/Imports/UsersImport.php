<?php

namespace App\Imports;

use App\Enums\BloodType;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\NppBpjsKetenagakerjaan;
use App\Enums\OvertimeSetting;
use App\Enums\PtkpStatus;
use App\Enums\Religion;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LiveAttendance;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, WithMultipleSheets
{
    use Importable;

    private User $user;
    private $emailVerifiedAt;
    private UserType $userType;
    private NppBpjsKetenagakerjaan $nppBpjsKetenagakerjaan;

    public function __construct()
    {
        $this->user = auth()->user();
        $this->emailVerifiedAt = now();
        $this->userType = UserType::USER;
        $this->nppBpjsKetenagakerjaan = NppBpjsKetenagakerjaan::DEFAULT;
    }

    public function sheets(): array
    {
        return [
            new UsersImport(),
        ];
    }

    public function rules(): array
    {
        return [
            'supervisor_nik' => ['nullable', 'exists:users,nik'],
            'role_id' => ['required', new CompanyTenantedRule(Role::class, 'Role not found')],
            'department_id' => ['required', new CompanyTenantedRule(Department::class, 'Department not found')],
            'position_id' => ['required', new CompanyTenantedRule(Position::class, 'Position not found')],
            'branch_id' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'live_attendance_id' => ['nullable', new CompanyTenantedRule(LiveAttendance::class, 'Live attendance not found')],
            'name' => 'required|min:2|max:100',
            'last_name' => 'nullable|min:2|max:100',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|min:6|max:50',
            'nik' => 'required|max:50',
            'phone' => 'required|max:20',
            'gender' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), Gender::getValues())) {
                    $fail('Invalid gender value.');
                }
            }],
            'join_date' => 'required|date',
            'sign_date' => 'nullable|date',

            'kk_number' => 'nullable|min:6|max:50',
            'ktp_number' => 'nullable|min:6|max:50',
            'postal_code' => 'nullable|min:3|max:10',
            'address' => 'nullable',
            'address_ktp' => 'nullable',
            'employment_status' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), EmploymentStatus::getValues())) {
                    $fail('Invalid Employment Status value.');
                }
            }],
            'passport_number' => 'nullable|min:6|max:50',
            'passport_expired' => 'nullable|date',
            'birth_place' => 'nullable',
            'birthdate' => 'nullable|date',
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'blood_type' => ['nullable', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), BloodType::getValues())) {
                    $fail('Invalid blood value.');
                }
            }],
            'rhesus' => 'nullable',
            'religion' => ['nullable', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), Religion::getValues())) {
                    $fail('Invalid religion value.');
                }
            }],

            'basic_salary' => 'required|numeric',
            'overtime_setting' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), OvertimeSetting::getValues())) {
                    $fail('Invalid Overtime Setting value.');
                }
            }],
            'bank_name' => 'nullable|min:3|max:100',
            'bank_account_number' => 'nullable|min:3|max:50',
            'bank_account_holder' => 'nullable|min:3|max:50',
            'secondary_bank_name' => 'nullable|min:3|max:100',
            'secondary_bank_account_number' => 'nullable|min:3|max:50',
            'secondary_bank_account_holder' => 'nullable|min:3|max:50',
            'npwp' => 'nullable|min:3|max:50',
            'ptkp_status' => ['required', Rule::enum(PtkpStatus::class)],
            'tax_method' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), TaxMethod::getValues())) {
                    $fail('Invalid Tax Method value.');
                }
            }],
            'tax_salary' => ['required', function ($attribute, $value, $fail) {
                if (!in_array(strtolower($value), TaxSalary::getValues())) {
                    $fail('Invalid Tax Salary value.');
                }
            }],
            'beginning_netto' => 'nullable|numeric',
            'pph_21_paid' => 'nullable|numeric',

            'bpjs_ketenagakerjaan_number' => 'nullable|min:6|max:50',
            'bpjs_ketenagakerjaan_date' => 'nullable|date',
            'bpjs_kesehatan_number' => 'nullable|min:6|max:50',
            'bpjs_kesehatan_family_number' => 'nullable|min:6|max:50',
            'bpjs_kesehatan_date' => 'nullable|date',
            'jaminan_pensiun_date' => 'nullable|date',
        ];
    }

    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        if (!isset($row['email']) || empty($row['email'])) {
            $row['email'] = str_replace(' ', '', strtolower($row['name'])) . "." . $row['nik'] . "@gmail.com";
        }

        $branch = Branch::select('id', 'company_id')->firstWhere('id', $row['branch_id']);
        $user = User::create([
            'group_id' => $this->user->group_id,
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'live_attendance_id' => $row['live_attendance_id'],
            // 'overtime_id',
            'name' => $row['name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            // 'work_email',
            'password' => $row['password'],
            'email_verified_at' => $this->emailVerifiedAt,
            'type' => $this->userType,
            'nik' => $row['nik'],
            'phone' => $row['phone'],
            'gender' => strtolower($row['gender']),
            'join_date' => date('Y-m-d', strtotime($row['join_date'])),
            'sign_date' => $row['sign_date'] ? date('Y-m-d', strtotime($row['sign_date'])) : date('Y-m-d', strtotime($row['join_date'])),
        ]);

        if (isset($row['supervisor_nik']) && !empty($row['supervisor_nik'])) {
            $supervisor = User::where('nik', trim($row['supervisor_nik']))->first(['id']);
            if ($supervisor) {
                $user->supervisors()->create([
                    'supervisor_id' => $supervisor->id,
                    'order' => 1,
                ]);
            }
        }

        $user->roles()->syncWithPivotValues([$row['role_id']], ['group_id' => $user->group_id]);

        $user->positions()->create([
            'department_id' => $row['department_id'],
            'position_id' => $row['position_id'],
        ]);

        // create user_details
        $user->detail()->create([
            'no_ktp' => $row['ktp_number'],
            'kk_no' => $row['kk_number'],
            'postal_code' => $row['postal_code'],
            'address' => $row['address'],
            'address_ktp' => $row['address_ktp'],
            'employment_status' => strtolower($row['employment_status']),
            'passport_no' => $row['passport_number'],
            'passport_expired' => date('Y-m-d', strtotime($row['passport_expired'])),
            'birth_place' => $row['birth_place'],
            'birthdate' => date('Y-m-d', strtotime($row['birthdate'])),
            'marital_status' => strtolower($row['marital_status']),
            'blood_type' => strtolower($row['blood_type']),
            'rhesus' => $row['blood_rhesus'],
            'religion' => strtolower($row['religion']),
        ]);

        // create user_payroll_infos
        $user->payrollInfo()->create([
            'basic_salary' => $row['basic_salary'],
            'overtime_setting' => strtolower($row['overtime_setting']),
            'bank_name' => $row['bank_name'],
            'bank_account_no' => $row['bank_account_number'],
            'bank_account_holder' => $row['bank_account_holder'],
            'secondary_bank_name' => $row['secondary_bank_name'],
            'secondary_bank_account_no' => $row['secondary_bank_account_number'],
            'secondary_bank_account_holder' => $row['secondary_bank_account_holder'],
            'npwp' => $row['npwp'],
            'ptkp_status' => $row['ptkp_status'],
            'tax_method' => strtolower($row['tax_method']),
            'tax_salary' => strtolower($row['tax_salary']),
            'beginning_netto' => $row['beginning_netto'],
            'pph_21_paid' => $row['pph_21_paid'],
        ]);

        // create user_bpjs
        $user->userBpjs()->create([
            'bpjs_ketenagakerjaan_no' => $row['bpjs_ketenagakerjaan_number'],
            'npp_bpjs_ketenagakerjaan' => $this->nppBpjsKetenagakerjaan,
            'bpjs_ketenagakerjaan_date' => date('Y-m-d', strtotime($row['bpjs_ketenagakerjaan_date'])),
            'bpjs_kesehatan_no' => $row['bpjs_kesehatan_number'],
            'bpjs_kesehatan_family_no' => $row['bpjs_kesehatan_family_number'],
            'bpjs_kesehatan_date' => $row['bpjs_kesehatan_date'] ? date('Y-m-d', strtotime($row['bpjs_kesehatan_date'])) : null,
            'bpjs_kesehatan_cost' => 'company',
            'jht_cost' => 'company',
            'jaminan_pensiun_cost' => 'company',
            'jaminan_pensiun_date' => $row['jaminan_pensiun_date'] ? date('Y-m-d', strtotime($row['jaminan_pensiun_date'])) : null,
        ]);
    }
}
