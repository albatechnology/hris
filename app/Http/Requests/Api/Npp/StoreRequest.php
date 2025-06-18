<?php

namespace App\Http\Requests\Api\Npp;

use App\Enums\JkkTier;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            'name' => 'required|string',
            'number' => 'required|numeric',
            'jkk_tier' => ['required', Rule::enum(JkkTier::class)],
        ];
    }
}
