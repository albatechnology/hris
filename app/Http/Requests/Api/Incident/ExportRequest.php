<?php

namespace App\Http\Requests\Api\Incident;

use App\Models\Branch;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Closure;
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
            'filter.incident_type_id' => ['nullable', 'exists:incident_types,id'],
            'filter.created_at_start_date' => ['nullable', 'date'],
            'filter.created_at_end_date' => ['nullable', 'date'],
        ];
    }
}
