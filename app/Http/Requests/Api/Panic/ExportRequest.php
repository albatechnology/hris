<?php

namespace App\Http\Requests\Api\Panic;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => 'nullable|array',
            'filter.company_id' => ['nullable', new CompanyTenantedRule()],
            'filter.branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'filter.created_start_date' => ['nullable', 'date'],
            'filter.created_end_date' => ['nullable', 'date'],
        ];
    }
}
