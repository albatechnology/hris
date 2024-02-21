<?php

namespace App\Http\Requests\Api\Schedule;

use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_overide_national_holiday' => $this->toBoolean($this->is_overide_national_holiday),
            'is_overide_company_holiday' => $this->toBoolean($this->is_overide_company_holiday),
            'is_include_late_in' => $this->toBoolean($this->is_include_late_in),
            'is_include_early_out' => $this->toBoolean($this->is_include_early_out),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', new CompanyTenantedRule],
            'name' => 'required|string',
            'effective_date' => 'required|date',
            'is_overide_national_holiday' => 'nullable|boolean',
            'is_overide_company_holiday' => 'nullable|boolean',
            'is_include_late_in' => 'nullable|boolean',
            'is_include_early_out' => 'nullable|boolean',
        ];
    }
}
