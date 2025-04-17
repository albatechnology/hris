<?php

namespace App\Http\Requests\Api\Reprimand;

use App\Enums\ReprimandType;
use App\Models\User;
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
            // 'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'assign_to' => ['required', new CompanyTenantedRule(User::class, 'Assigned user not found')],
            'type' => ['required', Rule::enum(ReprimandType::class)],
            'effective_date' => 'required|date',
            'end_date' => ['required', 'date', function ($attribute, $value, $fail) {
                if (date('Y-m-d', strtotime($value)) < date('Y-m-d', strtotime($this->effective_date))) {
                    $fail("End date must be greater than Effective date");
                }
            }],
            'notes' => 'nullable|string',
        ];
    }
}
