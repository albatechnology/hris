<?php

namespace App\Http\Requests\Api\Division;

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
            'company_id' => ['required', new CompanyTenantedRule()],
            'name' => ['required', 'string'],
        ];
    }
}
