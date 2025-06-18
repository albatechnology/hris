<?php

namespace App\Http\Requests\Api\ExtraOff;

use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

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
            'company_id' => ['required', new CompanyTenantedRule()],
            'user_ids' => 'required|array',
            'user_ids.*' => ['required', 'integer', function (string $attribute, mixed $value, Closure $fail) {
                if (DB::table('users')->where('id', $value)->where('company_id', $this->company_id)->doesntExist()) {
                    $fail('User not found');
                }
            }],
        ];
    }
}
