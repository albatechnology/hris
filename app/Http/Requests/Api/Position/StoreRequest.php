<?php

namespace App\Http\Requests\Api\Position;

use App\Models\Department;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'company_id' => [new CompanyTenantedRule()],
            'department_id' => ['required', new CompanyTenantedRule(Department::class, 'Department not found')],
            'name' => ['required', 'string'],
            'order' => ['required', 'integer'],
        ];
    }
}
