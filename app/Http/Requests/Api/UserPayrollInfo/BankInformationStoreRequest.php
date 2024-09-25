<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use Illuminate\Foundation\Http\FormRequest;

class BankInformationStoreRequest extends FormRequest
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
            'bank_name' => 'nullable|string',
            'bank_account_no' => 'nullable|string',
            'bank_account_holder' => 'nullable|string',
            'secondary_bank_name' => 'nullable|string',
            'secondary_bank_account_no' => 'nullable|string',
            'secondary_bank_account_holder' => 'nullable|string',
        ];
    }
}
