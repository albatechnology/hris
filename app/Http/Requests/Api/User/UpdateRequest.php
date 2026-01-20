<?php

namespace App\Http\Requests\Api\User;

use App\Enums\Gender;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = User::select('id')->tenanted()->where('id', $this->user)->firstOrFail();

        return [
            'group_id' => 'required|exists:groups,id',
            'company_id' => ['required', new CompanyTenantedRule()],
            'branch_id' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            // 'approval_id' => 'nullable|exists:users,id',
            // 'parent_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => ['nullable', function ($attribute, $value, $fail) use ($user) {
                if ($value && User::where('nik', $value)->where('id', '!=', $user->id)->exists()) {
                    $fail("NIK already taken");
                }
            }],
            'phone' => 'nullable',
            'gender' => ['required', Rule::enum(Gender::class)],
            'join_date' => 'nullable|date',
            'sign_date' => 'nullable|date',
            'end_contract_date' => 'nullable|date',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|exists:roles,id',

            'company_ids' => 'nullable|array',
            'company_ids.*' => ['required', new CompanyTenantedRule()],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
        ];
    }
}
