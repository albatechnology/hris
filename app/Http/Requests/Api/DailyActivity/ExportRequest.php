<?php

namespace App\Http\Requests\Api\DailyActivity;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'filter' => [
                ...($this->filter ?? []),
                'start_at' => $this->filter['start_at'] ?? date('Y-m-d'),
                'end_at' => $this->filter['end_at'] ?? date('Y-m-d'),
            ],
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
            'filter' => ['nullable', 'array'],
            'filter.company_id' => ['nullable', new CompanyTenantedRule()],
            'filter.branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'filter.user_ids' => ['nullable', 'string'],
            'filter.start_at' => ['nullable', 'date'],
            'filter.end_at' => ['nullable', 'date'],
        ];
    }
}
