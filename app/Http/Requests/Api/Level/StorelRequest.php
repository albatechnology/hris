<?php

namespace App\Http\Requests\Api\Level;

use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class StorelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            'name' => ['required', 'string'],
        ];
    }
}
