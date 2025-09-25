<?php

namespace App\Http\Requests\Api\User;

use App\Enums\BloodType;
use App\Enums\ClothesSize;
use App\Enums\EmploymentStatus;
use App\Enums\JobLevel;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetailStoreRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'nik' => 'nullable|string|unique:users,nik,' . $this->user,
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'join_date' => 'nullable|date',
            'positions' => 'nullable|array',
            'positions.*.position_id' => ['required', new CompanyTenantedRule(Position::class, 'Position not found')],
            'positions.*.department_id' => ['required', new CompanyTenantedRule(Department::class, 'Department not found')],
            'division_id'=>['required'],
            
        ];
    }
}
