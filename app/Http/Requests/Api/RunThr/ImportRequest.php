<?php

namespace App\Http\Requests\Api\RunThr;

use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isRequired = "required";
        if ($this->segment('4') != '') {
            $isRequired = "nullable";
        }

        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            'thr_date' => [$isRequired, 'date'],
            'payment_date' => [$isRequired, 'date'],
            'file' => [$isRequired, 'file', 'mimes:csv,xlsx', 'max:5120'],
        ];
    }
}
