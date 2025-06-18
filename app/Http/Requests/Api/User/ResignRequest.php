<?php

namespace App\Http\Requests\Api\User;

use App\Enums\ResignationReason;
use App\Enums\ResignationType;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResignRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->user,
            'type' => ResignationType::RESIGN->value,
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
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'type' => ['required', Rule::enum(ResignationType::class)],
            'reason' => ['required', Rule::enum(ResignationReason::class)],
            'resignation_date' => 'required|date',
            'merit_pay_amount' => 'required|numeric|min:0',
            'severance_amount' => 'required|numeric|min:0',
            'compensation_amount' => 'required|integer|min:0',
            'total_day_timeoff_compensation' => 'required|numeric|min:0',
            'timeoff_amount_per_day' => 'required|numeric|min:0',
            'total_timeoff_compensation' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }
}
