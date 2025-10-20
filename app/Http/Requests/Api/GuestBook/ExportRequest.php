<?php

namespace App\Http\Requests\Api\GuestBook;

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
            'filter.check_in_start_date' => ['nullable', 'date'],
            'filter.check_in_end_date' => ['nullable', 'date'],
        ];
    }
}
