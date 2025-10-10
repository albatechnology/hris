<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceType;
use App\Models\Schedule;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'user_id' => 'nullable|exists:users,id',
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'shift_id' => 'required|exists:shifts,id',
            'is_clock_in' => 'required|boolean',
            'time' => 'required',
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'note' => 'nullable|string',

            'file' => 'required_if:type,' . AttendanceType::AUTOMATIC->value . '|mimes:' . config('app.file_mimes_types'),
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
