<?php

namespace App\Imports;

use App\Enums\MaritalStatus;
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
            // $data['gender'] = strtolower($data['gender']);
            $data['password'] = '12345678';
            $data['email_verified_at'] = now();

            $data['marital_status'] = MaritalStatus::SINGLE;
            $user = User::create($data);
            unset($data['id']);

            // create branches
            $user->branches()->create([
                'branch_id' => $data['branch_id']
            ]);

            $company = Company::whereHas('branches', fn($q) => $q->where('id', $data['branch_id']))->firstOrFail();
            // create user_companies
            $user->companies()->create([
                'company_id' => $company->id
            ]);

            // create user_details
            $user->detail()->create($data);

            // create user_payroll_infos
            $user->payrollInfo()->create($data);

            // create user_bpjs
            $user->userBpjs()->create($data);

            // create user_schedules
            $user->schedules()->sync($user->company->schedules->pluck('id'));

            // set role
            $userRole = Role::where([
                'group_id' => $company->group_id,
                'name' => 'Role User ' . $company->name,
            ])->firstOrFail();
            DB::table('model_has_roles')->insert([
                'role_id' => $userRole->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'group_id' => $company->group_id,
            ]);

            $user->positions()->create([
                'department_id' => Department::whereHas('division', fn($q) => $q->where('company_id', $user->company_id))->where('name', 'HR')->firstOrFail(['id'])->id,
                'position_id' => Position::where('company_id', $user->company_id)->where('name', 'Manager')->firstOrFail(['id'])->id,
            ]);

            //     $count++;
            // }
        }
    }
}
