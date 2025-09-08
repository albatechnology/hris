<?php

namespace App\Http\Requests\Api\Panic;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'branch_id' => $this->branch_id ?? auth('sanctum')->user()->branch_id
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
            'branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'description' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['required', 'mimes:' . config('app.file_mimes_types')],
        ];
    }
}
