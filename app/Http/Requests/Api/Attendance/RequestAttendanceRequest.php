<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\AttendanceType;
use App\Models\Schedule;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestAttendanceRequest extends FormRequest
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
            'is_clock_out' => $this->toBoolean($this->is_clock_out),
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
            'user_id' => 'nullable|exists:users,id',
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'shift_id' => 'required|exists:shifts,id',
            'is_clock_in' => 'required|boolean',
            'is_clock_out' => 'required|boolean',
            'time' => 'required|date_format:Y-m-d H:i:s',
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'note' => 'nullable|string',
        ];
    }
}
