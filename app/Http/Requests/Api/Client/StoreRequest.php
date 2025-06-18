<?php

namespace App\Http\Requests\Api\Client;

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
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'pic_name' => 'nullable|string',
            'pic_email' => 'nullable|email',
            'pic_phone' => 'nullable|string',
        ];
    }
}
