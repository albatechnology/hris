<?php

namespace App\Http\Requests\Api\Overtime;

use App\Enums\RateType;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'name' => 'required|string',
            'is_rounding' => 'required|boolean',
            'compensation_rate_per_day' => 'nullable|numeric',
            'rate_type' => ['nullable', Rule::enum(RateType::class)],
            'rate_amount' => 'required|numeric',

            'overtime_roundings' => 'required_if:is_rounding,true|array',
            'overtime_roundings.*.start_minute' => 'integer',
            'overtime_roundings.*.end_minute' => 'integer|gt:overtime_roundings.*.start_minute',
            'overtime_roundings.*.rounded' => 'integer',

            'overtime_multipliers' => 'required_without:compensation_rate_per_day|array',
            'overtime_multipliers.*.is_weekday' => 'boolean',
            'overtime_multipliers.*.start_hour' => 'integer',
            'overtime_multipliers.*.end_hour' => 'integer|gt:overtime_multipliers.*.start_hour',
            'overtime_multipliers.*.multiply' => 'integer',
        ];
    }
}
