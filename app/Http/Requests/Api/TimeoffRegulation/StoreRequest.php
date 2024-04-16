<?php

namespace App\Http\Requests\Api\TimeoffRegulation;

use App\Enums\DaysName;
use App\Enums\TimeoffRenewType;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // 'is_allow_halfday' => $this->toBoolean($this->is_allow_halfday),
            'is_expired_in_end_period' => $this->toBoolean($this->is_expired_in_end_period),
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
            'renew_type' => ['required', Rule::enum(TimeoffRenewType::class)],
            'total_day' => 'nullable|integer',
            'start_period_date' => 'nullable|string|date_format:d',
            'start_period_month' => 'nullable|string|date_format:m',
            'end_period_date' => 'nullable|string|date_format:d',
            'end_period_month' => 'nullable|string|date_format:m',
            // 'max_consecutively_day' => 'nullable|integer',
            // 'is_allow_halfday' => 'nullable|boolean',
            'is_expired_in_end_period' => 'nullable|boolean',
            'expired_max_month' => 'nullable|integer',
            'min_working_month' => 'required|integer',
            'cut_off_date' => 'required|string|date_format:d',
            'min_advanced_leave_working_month' => 'nullable|integer',
            'max_advanced_leave_request' => 'nullable|integer',
            'dayoff_consecutively_working_day' => 'nullable|integer',
            'dayoff_consecutively_amount' => 'nullable|integer',
            'halfday_not_applicable_in' => 'nullable|array',
            'halfday_not_applicable_in.*' => ['required', 'string', Rule::enum(DaysName::class)],
        ];
    }
}
