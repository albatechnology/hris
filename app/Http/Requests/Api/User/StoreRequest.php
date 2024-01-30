<?php

namespace App\Http\Requests\Api\User;

use App\Enums\BloodType;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Enums\UserType;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
        // dd($this->all());
        return [
            'group_id' => 'nullable|exists:groups,id',
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, "Branch not found")],
            'manager_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => 'nullable',
            'phone' => 'nullable',
            'birth_place' => 'nullable',
            'birthdate' => 'nullable|date_format:Y-m-d',
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|exists:roles,id',

            'company_ids' => 'nullable|array',
            'company_ids.*' => ['required', new CompanyTenantedRule()],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['required', new CompanyTenantedRule(Branch::class, "Branch not found")],
        ];
    }
}
