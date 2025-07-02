<?php

namespace App\Http\Requests\Api\ReimbursementCategory;

use App\Enums\ReimbursementPeriodType;
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
            'company_id' => [new CompanyTenantedRule()],
            'name' => ['required', 'string', 'max:100'],
            'period_type' => ['required', Rule::enum(ReimbursementPeriodType::class)],
            'limit_amount' => ['required', 'integer', 'min:0', 'max:4000000000'],
        ];
    }
}
