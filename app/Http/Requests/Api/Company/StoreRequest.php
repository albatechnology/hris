<?php

namespace App\Http\Requests\Api\Company;

use App\Models\Country;
use App\Models\Group;
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
            'country_id' => Country::where('name', 'indonesia')->first()?->id,
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
            'group_id' => ['required', new CompanyTenantedRule(Group::class, 'Group not found')],
            'name' => 'required|string',
            'country_id' => 'required|integer',
            'country' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'address' => 'nullable|string',
            'employee_prefix' => 'nullable|string'

            // timeoff_regulations
            // 'renew_type' => ['required', Rule::enum(TimeoffRenewType::class)],
        ];
    }
}
