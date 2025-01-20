<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\AttendanceType;
use App\Models\Schedule;
use App\Models\Shift;
use App\Rules\CompanyTenantedRule;
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
            'is_clock_in' => $this->toBoolean($this->is_clock_in),
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
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'shift_id' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found')],
            'is_clock_in' => 'required|boolean',
            'time' => 'required|date_format:Y-m-d H:i:s',
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'note' => 'nullable|string',

            'file' => 'required|mimes:' . config('app.file_mimes_types'),
            // 'file' => 'required_if:type,' . AttendanceType::AUTOMATIC->value . '|mimes:' . config('app.file_mimes_types'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required_if' => 'Selfie photo is required.',
        ];
    }
}
