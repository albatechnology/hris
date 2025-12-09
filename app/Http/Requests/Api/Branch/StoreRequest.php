<?php

namespace App\Http\Requests\Api\Branch;

use App\Models\Branch;
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
            'parent_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found'), function ($attribute, $value, $fail) {
                if (Branch::where('id', $value)->whereNull('parent_id')->doesntExist()) {
                    $fail('Parent branch not found');
                }
            }],
            'company_id' => [new CompanyTenantedRule()],
            'name' => ['required', 'string'],
            'country' => ['nullable', 'string'],
            'province' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string'],
            'lat' => ['nullable', 'string'],
            'lng' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'umk' => ['nullable', 'string'],
            'pic_name' => ['nullable', 'string'],
            'pic_email' => ['nullable', 'string'],
            'pic_phone' => ['nullable', 'string'],
        ];
    }
}
