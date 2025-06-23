<?php

namespace App\Http\Requests;

use App\Enums\ApprovalStatus;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'filter' => [
                'approval_status' => $this->filter['approval_status'] ?? ApprovalStatus::ON_PROGRESS->value
            ]
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
            'filter.approval_status' => ['required', Rule::enum(ApprovalStatus::class)],
            'filter.branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'filter.name' => ['nullable', 'string'],
            'filter.created_at' => ['nullable', 'created_at'],
        ];
    }
}
