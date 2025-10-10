<?php

namespace App\Http\Requests\Api\Reprimand;

use App\Enums\ReprimandType;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
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
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            // 'assign_to' => ['required', new CompanyTenantedRule(User::class, 'Assigned user not found')],
            'type' => ['required', Rule::enum(ReprimandType::class)],
            'effective_date' => 'required|date',
            'end_date' => ['required', 'date', function ($attribute, $value, $fail) {
                if (date('Y-m-d', strtotime($value)) < date('Y-m-d', strtotime($this->effective_date))) {
                    $fail("End date must be greater than Effective date");
                }
            }],
            'notes' => 'nullable|string',
            'watcher_ids' => 'nullable|array',
            'watcher_ids.*' => ['required', new CompanyTenantedRule(User::class, 'Watcher not found')],
            'file' => 'nullable|mimes:' . config('app.file_mimes_types'),
        ];
    }
}
