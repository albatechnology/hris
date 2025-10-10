<?php

namespace App\Http\Requests\Api\Company;

use App\Models\Group;
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
            'group_id' => ['required', new CompanyTenantedRule(Group::class, 'Group not found')],
            'name' => 'required|string',
            'country_id' => 'nullable|integer',
            'country' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'address' => 'nullable|string',
            'employee_prefix' => 'nullable|string'
        ];
    }
}
