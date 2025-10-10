<?php

namespace App\Http\Requests\Api\ReimbursementCategory;

use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class AddUsersRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'users' => 'required|array',
            'users.*.id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'users.*.limit_amount' => ['required', 'integer', 'min:0', 'max:4000000000'],
        ];
    }
}
