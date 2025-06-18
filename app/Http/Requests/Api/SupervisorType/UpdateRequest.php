<?php

namespace App\Http\Requests\Api\SupervisorType;

use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'name' => 'required|string',
            'order' => 'required|integer',
        ];
    }
}
