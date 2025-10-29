<?php

namespace App\Http\Requests\Api\Attendance;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class ChildrenRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'filter' => [
                ...($this->filter ?? []),
                'date' => !empty($this->filter['date']) ? $this->filter['date'] : date('Y-m-d'),
                'is_show_resign_users' => isset($this->filter['is_show_resign_users']) ? $this->toBoolean($this->filter['is_show_resign_users']) : null,
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
            'filter' => 'nullable|array',
            'filter.branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'filter.date' => 'nullable|date',
            'filter.is_show_resign_users' => 'nullable|boolean',
            'sort' => 'nullable|string',
        ];
    }
}
