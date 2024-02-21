<?php

namespace App\Http\Requests\Api\Shift;

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
            'is_enable_validation' => $this->toBoolean($this->is_enable_validation),
            'is_enable_grace_period' => $this->toBoolean($this->is_enable_grace_period),
            'is_enable_auto_overtime' => $this->toBoolean($this->is_enable_auto_overtime),
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
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'color' => 'nullable|string|min:4|max:7',
            'description' => 'nullable|string',
            'is_enable_validation' => 'nullable|boolean',
            'clock_in_min_before' => 'nullable|integer',
            'clock_out_max_after' => 'nullable|integer',
            'is_enable_grace_period' => 'nullable|boolean',
            'clock_in_dispensation' => 'nullable|integer',
            'clock_out_dispensation' => 'nullable|integer',
            'is_enable_auto_overtime' => 'nullable|boolean',
            'overtime_before' => 'nullable|date_format:H:i',
            'overtime_after' => 'nullable|date_format:H:i',
        ];
    }
}
