<?php

namespace App\Http\Requests\Api\UserTransfer;

use App\Enums\EmploymentStatus;
use App\Enums\TransferType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Closure;
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
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', Rule::enum(TransferType::class)],
            'effective_date' => ['required', 'date'],
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', function ($attribute, $value, Closure $fail) {
                if (
                    Branch::tenanted()->where('id', $value)
                    ->when($this->company_id, fn($q) => $q->where('company_id', $this->company_id))
                    ->doesntExist()
                ) {
                    $fail("Branch not found");
                };
            }],
            'supervisor_id' => ['nullable', function ($attribute, $value, Closure $fail) {
                if (
                    User::tenanted()->where('id', $value)
                    ->when($this->company_id, fn($q) => $q->where('company_id', $this->company_id))
                    ->doesntExist()
                ) {
                    $fail("Supervisor not found");
                };
            }],
            'position_id' => ['required_with:department_id', function ($attribute, $value, Closure $fail) {
                if (
                    Position::tenanted()->where('id', $value)
                    ->when($this->company_id, fn($q) => $q->where('company_id', $this->company_id))
                    ->doesntExist()
                ) {
                    $fail("Position not found");
                };
            }],
            'department_id' => ['required_with:position_id', function ($attribute, $value, Closure $fail) {
                if (
                    Department::tenanted()->where('id', $value)
                    ->when($this->company_id, fn($q) => $q->whereHas('division', fn($q) => $q->where('company_id', $this->company_id)))
                    ->doesntExist()
                ) {
                    $fail("Department not found");
                };
            }],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'reason' => ['required', 'string'],
            'file' => ['nullable', 'mimes:' . config('app.file_mimes_types')],

            // 'branch_ids' => 'nullable|array',
            // 'branch_ids.*' => 'required|exists:branches,id',

            // 'positions' => 'nullable|array',
            // 'positions.*.position_id' => 'required|exists:positions,id',
            // 'positions.*.department_id' => 'required|exists:departments,id',
        ];
    }
}
