<?php

namespace App\Http\Requests\Api\User;

use App\Enums\Gender;
use App\Enums\UserType;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'approval_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$this->user->id,
            'password' => 'nullable|string',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => 'nullable',
            'phone' => 'nullable',
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|exists:roles,id',

            'company_ids' => 'nullable|array',
            'company_ids.*' => ['required', new CompanyTenantedRule()],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
        ];
    }
}
