<?php

namespace App\Http\Requests\Api\User;

use App\Enums\EmploymentStatus;
use App\Enums\ResignationType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RehireRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->user,
            'type' => ResignationType::REHIRE->value,
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
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'type' => ['required', Rule::enum(ResignationType::class)],
            'resignation_date' => 'required|date',
            'nik' => 'required|string',
            'branch_id' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'department_id' => ['required', new CompanyTenantedRule(Department::class, 'Department not found')],
            'position_id' => ['required', new CompanyTenantedRule(Position::class, 'Position not found')],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'basic_salary' => 'required|numeric',
            'description' => 'nullable|string',
        ];
    }
}
