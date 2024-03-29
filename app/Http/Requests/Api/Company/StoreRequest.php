<?php

namespace App\Http\Requests\Api\Company;

use App\Enums\TimeoffRenewType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
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
    // protected function prepareForValidation()
    // {
    //     $this->merge([
    //         // 'is_allow_halfday' => $this->toBoolean($this->is_allow_halfday),
    //         'is_expired_in_end_period' => $this->toBoolean($this->is_expired_in_end_period),
    //     ]);
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_id' => 'required|exists:groups,id',
            'name' => 'required|string',
            'country' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'address' => 'nullable|string',

            // timeoff_regulations
            'renew_type' => ['required', Rule::enum(TimeoffRenewType::class)],
            // 'total_day' => 'nullable|integer',
            // 'start_period' => 'nullable|string|date_format:m-d',
            // 'end_period' => 'nullable|string|date_format:m-d',
            // 'max_consecutively_day' => 'nullable|integer',
            // 'is_expired_in_end_period' => 'nullable|boolean',
            // 'expired_max_month' => 'nullable|integer',
            // 'min_working_month' => 'required|integer',
            // 'cut_off_date' => 'required|string|date_format:d',
            // 'min_advanced_leave_working_month' => 'nullable|integer',
            // 'max_advanced_leave_request' => 'nullable|integer',
            // 'dayoff_consecutively_working_day' => 'nullable|integer',
            // 'dayoff_consecutively_amount' => 'nullable|integer',
            // 'halfday_not_applicable_in' => 'nullable|array',
            // 'halfday_not_applicable_in.*' => 'required|string|date_format:d',
        ];
    }
}
