<?php

namespace App\Http\Requests\Api\RunPayroll;

use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
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
            'period' => [$isRequired, 'string', function (string $attr, string $value, Closure $fail) {
                // $runPayroll = RunPayroll::where('period', $value)->exists();
                // if ($runPayroll) {
                //     $fail('Cannot run payroll in the same period');
                // }
            }],
            'payment_schedule' => [$isRequired, 'date'],
            'file' => [$isRequired, 'file', 'mimes:csv,xlsx', 'max:5120'],
        ];
    }
}
