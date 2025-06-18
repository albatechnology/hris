<?php

namespace App\Http\Requests\Api\Overtime;

use App\Models\Overtime;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class UserSettingRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', new CompanyTenantedRule(User::class, "User not found")],
            'overtime_ids' => 'array|nullable',
            'overtime_ids.*' => ['required', new CompanyTenantedRule(Overtime::class, "Overtime not found")],
        ];
    }
}
