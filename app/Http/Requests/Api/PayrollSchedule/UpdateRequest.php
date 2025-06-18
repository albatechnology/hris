<?php

namespace App\Http\Requests\Api\PayrollSchedule;

use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'cut_off_date' => 'required|date_format:d',
        ];
    }
}
