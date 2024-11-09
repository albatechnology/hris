<?php

namespace App\Imports;

use App\Enums\MaritalStatus;
use App\Enums\MediaCollection;
use App\Enums\PaidBy;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserSunImport implements ToCollection, WithHeadingRow
{
    use Importable;

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // $count = 1;
        foreach ($collection as $data) {
            // if ($count == 1) {
            $data = $data->toArray();
            unset($data['id']);
            unset($data['name']);
            // $data['gender'] = strtolower($data['gender']);
            $data['taxable_date'] = date('Y-m-d', strtotime($data['taxable_date']));
            $data['bpjs_ketenagakerjaan_date'] = date('Y-m-d', strtotime($data['bpjs_ketenagakerjaan_date']));
            $data['bpjs_kesehatan_date'] = date('Y-m-d', strtotime($data['bpjs_kesehatan_date']));
            $data['jaminan_pensiun_date'] = date('Y-m-d', strtotime($data['jaminan_pensiun_date']));
            // $data['marital_status'] = MaritalStatus::SINGLE;
            $user = User::firstWhere('email', $data['email']);
            $user->clearMediaCollection(MediaCollection::USER->value);
            if ($user) {
                unset($data['email']);

                $file = public_path('users/' . $user->email . '.jpg');
                $fileExist = file_exists($file);
                if ($fileExist) {
                    $user
                        ->addMedia($file)
                        ->preservingOriginal()
                        ->toMediaCollection(MediaCollection::USER->value);
                }

                // create user_details
                $user->detail()->update([
                    'no_ktp' => $data['no_ktp'],
                    'kk_no' => $data['kk_no'],
                    'postal_code' => $data['postal_code'],
                    'address' => $data['address'],
                    'address_ktp' => $data['address_ktp'],
                    'job_position' => $data['job_position'],
                    'job_level' => $data['job_level'],
                    'employment_status' => $data['employment_status'],
                    'passport_no' => $data['passport_no'],
                    'passport_expired' => $data['passport_expired'],
                    'birth_place' => $data['birth_place'],
                    'birthdate' => $data['birthdate'],
                    'marital_status' => $data['marital_status'],
                ]);

                // create user_payroll_infos
                $user->payrollInfo()->update([
                    'basic_salary' => $data['basic_salary'],
                    'salary_type' => $data['salary_type'],
                    'payment_schedule' => $data['payment_schedule'],
                    'prorate_setting' => $data['prorate_setting'],
                    'overtime_setting' => $data['overtime_setting'],
                    'cost_center_category' => $data['cost_center_category'],
                    'currency' => $data['currency'],
                    'bank_name' => $data['bank_name'],
                    'bank_account_no' => $data['bank_account_no'],
                    'bank_account_holder' => $data['bank_account_holder'],
                    'secondary_bank_name' => $data['secondary_bank_name'],
                    'secondary_bank_account_no' => $data['secondary_bank_account_no'],
                    'secondary_bank_account_holder' => $data['secondary_bank_account_holder'],
                    'npwp' => $data['npwp'],
                    'ptkp_status' => $data['ptkp_status'],
                    'tax_method' => $data['tax_method'],
                    'tax_salary' => $data['tax_salary'],
                    'taxable_date' => $data['taxable_date'],
                    'employee_tax_status' => $data['employee_tax_status'],
                    'beginning_netto' => $data['beginning_netto'],
                    'pph21_paid' => $data['pph21_paid'],
                ]);

                // create user_bpjs
                $user->userBpjs()->update([
                    'bpjs_ketenagakerjaan_no' => $data['bpjs_ketenagakerjaan_no'],
                    // 'npp_bpjs_ketenagakerjaan' => $data['npp_bpjs_ketenagakerjaan'],
                    'bpjs_ketenagakerjaan_date' => $data['bpjs_ketenagakerjaan_date'],
                    'bpjs_kesehatan_no' => $data['bpjs_kesehatan_no'],
                    'bpjs_kesehatan_family_no' => $data['bpjs_kesehatan_family_no'],
                    'bpjs_kesehatan_date' => $data['bpjs_kesehatan_date'],
                    'bpjs_kesehatan_cost' => $data['bpjs_kesehatan_cost'] ?? PaidBy::EMPLOYEE,
                    'jht_cost' => $data['jht_cost'] ?? PaidBy::EMPLOYEE,
                    'jaminan_pensiun_cost' => $data['jaminan_pensiun_cost'] ?? PaidBy::EMPLOYEE,
                    'jaminan_pensiun_date' => $data['jaminan_pensiun_date'],
                ]);
            }
        }
    }

    // public function collection(Collection $collection)
    // {
    //     // $count = 1;
    //     foreach ($collection as $data) {
    //         // if ($count == 1) {
    //         $data = $data->toArray();
    //         // $data['gender'] = strtolower($data['gender']);
    //         $data['password'] = '12345678';
    //         $data['email_verified_at'] = now();

    //         $data['marital_status'] = MaritalStatus::SINGLE;
    //         $user = User::create($data);
    //         unset($data['id']);

    //         // create branches
    //         $user->branches()->create([
    //             'branch_id' => $data['branch_id']
    //         ]);

    //         $company = Company::whereHas('branches', fn($q) => $q->where('id', $data['branch_id']))->firstOrFail();
    //         // create user_companies
    //         $user->companies()->create([
    //             'company_id' => $company->id
    //         ]);

    //         // create user_details
    //         $user->detail()->create($data);

    //         // create user_payroll_infos
    //         $user->payrollInfo()->create($data);

    //         // create user_bpjs
    //         $user->userBpjs()->create($data);

    //         // create user_schedules
    //         $user->schedules()->sync($user->company->schedules->pluck('id'));

    //         // set role
    //         $userRole = Role::where([
    //             'group_id' => $company->group_id,
    //             'name' => 'Role User ' . $company->name,
    //         ])->firstOrFail();
    //         DB::table('model_has_roles')->insert([
    //             'role_id' => $userRole->id,
    //             'model_type' => get_class($user),
    //             'model_id' => $user->id,
    //             'group_id' => $company->group_id,
    //         ]);

    //         $user->positions()->create([
    //             'department_id' => Department::whereHas('division', fn($q) => $q->where('company_id', $user->company_id))->where('name', 'HR')->firstOrFail(['id'])->id,
    //             'position_id' => Position::where('company_id', $user->company_id)->where('name', 'Manager')->firstOrFail(['id'])->id,
    //         ]);

    //         //     $count++;
    //         // }
    //     }
    // }
}
