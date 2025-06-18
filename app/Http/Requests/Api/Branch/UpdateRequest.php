<?php

namespace App\Http\Requests\Api\Branch;

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
            'company_id' => [new CompanyTenantedRule()],
            'name' => 'required|string',
            'country' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'address' => 'nullable|string',
            'umk' => 'nullable|string',
            'pic_name' => 'nullable|string',
            'pic_email' => 'nullable|string',
            'pic_phone' => 'nullable|string',
        ];
    }
}
