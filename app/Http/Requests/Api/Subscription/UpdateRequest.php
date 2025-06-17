<?php

namespace App\Http\Requests\Api\Subscription;

use App\Models\Subscription;
use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
                    if (Subscription::tenanted()->where('account_no', $value)->whereNot('id', $this->Subscription)->exists()) {
                        $fail('Account no already exist');
                    }
                }
            ],
            'account_holder' => 'required|string',
            'code' => [
                'required',
                'string',
                function (mixed $attribute, string $value, Closure $fail) {
                    if (Subscription::tenanted()->where('code', $value)->whereNot('id', $this->Subscription)->exists()) {
                        $fail('Code already exist');
                    }
                }
            ],
            'branch' => 'required|string',
        ];
    }
}
