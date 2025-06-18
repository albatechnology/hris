<?php

namespace App\Http\Requests\Api\User;

use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
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
            'filter.is_active' => !empty($this->filter['is_active']) ? $this->toBoolean($this->is_active) : null,
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
            'is_json' => ['nullable', 'boolean'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'filter.start_date' => 'nullable|date',
            'filter.end_date' => 'nullable|date',
            'filter.is_active' => 'nullable|boolean',
        ];
    }
}
