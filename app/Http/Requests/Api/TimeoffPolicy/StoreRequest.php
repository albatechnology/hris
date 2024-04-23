<?php

namespace App\Http\Requests\Api\TimeoffPolicy;

use App\Enums\TimeoffPolicyType;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_allow_halfday' => $this->toBoolean($this->is_allow_halfday),
            'is_for_all_user' => $this->toBoolean($this->is_for_all_user),
            'is_enable_block_leave' => $this->toBoolean($this->is_enable_block_leave),
            // 'is_unlimited_day' => $this->toBoolean($this->is_unlimited_day),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            'type' => ['required', Rule::enum(TimeoffPolicyType::class)],
            'name' => 'required|string',
            'code' => 'required|string',
            'description' => 'nullable|string',
            'effective_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'max_consecutively_day' => 'nullable|integer',
            'is_allow_halfday' => 'nullable|boolean',
            'is_for_all_user' => 'nullable|boolean',
            'is_enable_block_leave' => 'nullable|boolean',
            'block_leave_take_days' => 'nullable|integer',
            'block_leave_min_working_month' => 'nullable|integer',
            'max_used' => 'nullable|integer',

            'user_ids' => ['required_if:is_for_all_user,false', 'array'],
            'user_ids.*' => 'required|exists:users,id',
        ];
    }
}
