<?php

namespace App\Http\Requests\Api\Bank;

use App\Models\Bank;
use App\Rules\CompanyTenantedRule;
use Closure;
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
            'account_no' => [
                'required',
                'string',
                function (mixed $attribute, string $value, Closure $fail) {
                    if (Bank::tenanted()->where('account_no', $value)->exists()) {
                        $fail('Account no already exist');
                    }
                }
            ],
            'account_holder' => 'required|string',
            'code' => [
                'required',
                'string',
                function (mixed $attribute, string $value, Closure $fail) {
                    if (Bank::tenanted()->where('code', $value)->exists()) {
                        $fail('Code already exist');
                    }
                }
            ],
            'branch' => 'required|string',
        ];
    }
}
