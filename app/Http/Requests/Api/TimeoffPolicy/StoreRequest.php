<?php

namespace App\Http\Requests\Api\TimeoffPolicy;

use App\Enums\TimeoffPolicyType;
use App\Rules\CompanyTenantedRule;
use App\Traits\RequestToBoolean;
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
            'is_for_all_user' => $this->toBoolean($this->is_for_all_user),
            'is_enable_block_leave' => $this->toBoolean($this->is_enable_block_leave),
            'is_unlimited_day' => $this->toBoolean($this->is_unlimited_day),
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
            'is_for_all_user' => 'nullable|boolean',
            'is_enable_block_leave' => 'nullable|boolean',
            'is_unlimited_day' => 'nullable|boolean',
        ];
    }
}
