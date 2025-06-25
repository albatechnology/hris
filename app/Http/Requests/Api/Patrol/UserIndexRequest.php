<?php

namespace App\Http\Requests\Api\Patrol;

use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
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
                'start_date' => !empty($this->filter['start_date']) ? $this->filter['start_date'] : null,
                'end_date' => !empty($this->filter['end_date']) ? $this->filter['end_date'] : null,
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
            'filter.start_date' => 'nullable|date',
            'filter.end_date' => 'nullable|date',
        ];
    }
}
