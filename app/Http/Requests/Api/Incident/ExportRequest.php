<?php

namespace App\Http\Requests\Api\Incident;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
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
            'filter' => 'nullable|array',
            'filter.company_id' => ['nullable', new CompanyTenantedRule()],
            // 'filter.client_id' => ['nullable', new CompanyTenantedRule(Client::class, 'Client not found')],
            'filter.branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'filter.check_in_start_date' => ['nullable', 'date'],
            'filter.check_in_end_date' => ['nullable', 'date'],
        ];
    }
}
