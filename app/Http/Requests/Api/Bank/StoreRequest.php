<?php

namespace App\Http\Requests\Api\Bank;

use App\Models\Bank;
use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
                    if (Bank::tenanted()->where('account_no', $value)->exist()) {
                        $fail('Account no already exist');
                    }
                }
            ],
            'account_holder' => 'required|string',
            'code' => [
                'required',
                'string',
                function (mixed $attribute, string $value, Closure $fail) {
                    if (Bank::tenanted()->where('code', $value)->exist()) {
                        $fail('Code already exist');
                    }
                }
            ],
            'branch' => 'required|string',
        ];
    }
}
