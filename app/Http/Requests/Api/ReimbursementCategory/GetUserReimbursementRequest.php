<?php

namespace App\Http\Requests\Api\ReimbursementCategory;

use App\Models\ReimbursementCategory;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class GetUserReimbursementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.reimbursement_category_id' => ['nullable', new CompanyTenantedRule(ReimbursementCategory::class, 'Reimbursement category not found')],
            'filter.month' => ['nullable', 'date_format:m'],
            'filter.year' => ['nullable', 'date_format:Y'],
        ];
    }
}
