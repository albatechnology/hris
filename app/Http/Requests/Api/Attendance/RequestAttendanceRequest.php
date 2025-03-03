<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\AttendanceType;
use App\Models\Schedule;
use App\Models\Shift;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
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
            'type' => AttendanceType::MANUAL->value,
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
            'schedule_id' => ['required', new CompanyTenantedRule(model: Schedule::class, message: 'Schedule not found', outsideQuery: fn($q) => $q->withTrashed())],
            'shift_id' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found', fn($q) => $q->orWhereNull('company_id'), fn($q) => $q->withTrashed())],
            'is_clock_in' => ['boolean', function (string $attribute, mixed $value, Closure $fail) {
                if (!$value && !$this->is_clock_out) $fail("The {$attribute} field is required.");
            },],
            'clock_in_hour' => 'required_if:is_clock_in,true|date_format:H:i',
            'is_clock_out' => ['boolean', function (string $attribute, mixed $value, Closure $fail) {
                if (!$value && !$this->is_clock_in) $fail("The {$attribute} field is required.");
            },],
            'clock_out_hour' => 'required_if:is_clock_out,true|date_format:H:i',
            'date' => 'required|date_format:Y-m-d',
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'note' => 'nullable|string',
        ];
    }
}
