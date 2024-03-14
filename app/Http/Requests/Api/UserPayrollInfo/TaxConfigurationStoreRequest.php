<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Enums\EmploymentStatus;
use App\Enums\PtkpStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxSalary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxConfigurationStoreRequest extends FormRequest
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
            'npwp' => 'nullable|string',
            'ptkp_status' => ['required', Rule::enum(PtkpStatus::class)],
            'tax_method' => ['required', Rule::enum(TaxMethod::class)],
            'tax_salary' => ['required', Rule::enum(TaxSalary::class)],
            'taxable_date' => 'nullable|date_format:Y-m-d',
            'employee_tax_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'beginning_netto' => 'required|integer',
            'pph21_paid' => 'required|integer',
        ];
    }
}
