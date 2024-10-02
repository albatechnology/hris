<?php

namespace App\Http\Requests\Api\Attendance;

use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'date' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
        ];
    }
}
