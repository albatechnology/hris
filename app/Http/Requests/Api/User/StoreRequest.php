<?php

namespace App\Http\Requests\Api\User;

use App\Enums\Gender;
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
        return [
            'group_id' => 'nullable|exists:groups,id',
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            // 'approval_id' => 'nullable|exists:users,id',
            // 'parent_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email|unique:users,email',
            'work_email' => 'required|email',
            'password' => 'required|string',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => 'required|max:20',
            'phone' => 'nullable',
            'gender' => ['required', Rule::enum(Gender::class)],
            'join_date' => 'nullable|date',
            'sign_date' => 'nullable|date',
            'end_contract_date' => 'nullable|date',
            'resign_date' => 'nullable|date',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|exists:roles,id',

            'company_ids' => 'nullable|array',
            'company_ids.*' => ['required', new CompanyTenantedRule()],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
        ];
    }
}
