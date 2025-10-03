<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Models\Bank;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class BankInformationStoreRequest extends FormRequest
{


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
            'bank_id' => ['required', new CompanyTenantedRule(Bank::class, 'Bank not found')],
            'tabungan_haji_no' => 'nullable|string',
            'epf_no' => 'nullable|string'
        ];
    }
}
