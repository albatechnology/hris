<?php

namespace App\Http\Requests\Api\RunPayroll;

use App\Models\Bank;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
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
            'bank_id' => ['required', new CompanyTenantedRule(Bank::class, 'Bank not found')],
        ];
    }
}
