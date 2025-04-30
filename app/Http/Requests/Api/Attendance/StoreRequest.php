<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\AttendanceType;
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
        $isOfflineMode = $this->toBoolean($this->is_offline_mode ?? 0);

        $type = $this->type;
        if ($isOfflineMode) {
            $type = AttendanceType::MANUAL->value;
        }

        $this->merge([
            'is_clock_in' => $this->toBoolean($this->is_clock_in),
            'type' => $type,
            'is_offline_mode' => $isOfflineMode,
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
            'schedule_id' => ['required_if:is_offline_mode,false', 'exists:schedules,id'],
            // 'schedule_id' => ['required', new CompanyTenantedRule(model: Schedule::class, message: 'Schedule not found', outsideQuery: fn($q) => $q->withTrashed())],
            'shift_id' => ['required_if:is_offline_mode,false', 'exists:shifts,id'],
            // 'shift_id' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found', fn($q) => $q->orWhereNull('company_id'), fn($q) => $q->withTrashed())],
            'is_clock_in' => 'required|boolean',
            'time' => 'required|date_format:Y-m-d H:i:s',
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'note' => 'nullable|string',
            'is_offline_mode' => 'required|boolean',

            'file' => 'required_if:is_offline_mode,false|mimes:' . config('app.file_mimes_types'),
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
