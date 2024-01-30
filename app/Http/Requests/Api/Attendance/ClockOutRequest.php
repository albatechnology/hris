<?php

namespace App\Http\Requests\Api\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
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
            'user_id' => 'nullable|exists:users,id',
            'schedule_id' => 'required|exists:schedules,id',
            'shift_id' => 'required|exists:shifts,id',
            // 'clock_in' => 'required|date_format:Y-m-d H:i:s',
            'clock_out' => 'nullable|date_format:Y-m-d H:i:s',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ];
    }
}
