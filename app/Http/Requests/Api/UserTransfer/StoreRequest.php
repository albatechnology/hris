<?php

namespace App\Http\Requests\Api\UserTransfer;

use App\Enums\EmploymentStatus;
use App\Enums\TransferType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'type' => ['required', Rule::enum(TransferType::class)],
            'effective_date' => 'required|date',
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'supervisor_id' => ['nullable', new CompanyTenantedRule(User::class, 'Supervisor not found')],
            'position_id' => ['required_with:department_id', new CompanyTenantedRule(Position::class, 'Position not found')],
            'department_id' => ['required_with:position_id', new CompanyTenantedRule(Department::class, 'Department not found')],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'reason' => 'required|string',
            'file' => ['nullable', 'mimes:' . config('app.file_mimes_types')],

            // 'branch_ids' => 'nullable|array',
            // 'branch_ids.*' => 'required|exists:branches,id',

            // 'positions' => 'nullable|array',
            // 'positions.*.position_id' => 'required|exists:positions,id',
            // 'positions.*.department_id' => 'required|exists:departments,id',
        ];
    }
}
