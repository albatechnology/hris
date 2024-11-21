<?php

namespace App\Http\Requests\Api\TimeoffQuota;

use App\Models\TimeoffPolicy;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'timeoff_policy_id' => ['required', new CompanyTenantedRule(TimeoffPolicy::class, 'Timeoff policy not found')],
            'user_id' => 'required',
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'nullable|date',
            'quota' => 'required|numeric|min:0.5|multiple_of:0.5',
            'description' => 'required|string',
        ];
    }
}
