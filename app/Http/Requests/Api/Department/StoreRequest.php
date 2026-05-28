<?php

namespace App\Http\Requests\Api\Department;

use App\Models\Division;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'user_id' => ['nullable', new CompanyTenantedRule(User::class, 'User not found')],
            'division_id' => ['required', new CompanyTenantedRule(Division::class, 'Division not found')],
            'name' => ['required', 'string'],
        ];
    }
}
