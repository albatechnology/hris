<?php

namespace App\Http\Requests\Api\CustomField;

use App\Enums\FieldType;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'key' => 'required|string',
            'type' => ['nullable', Rule::enum(FieldType::class)],
            'options' => 'nullable|array',
        ];
    }
}
