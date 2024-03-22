<?php

namespace App\Http\Requests\Api\OvertimeRequest;

use App\Models\Schedule;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'shift_id' => 'required|exists:shifts,id',
            'overtime_id' => 'required|exists:overtimes,id',
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'end_at' => 'required|date_format:Y-m-d H:i:s',
            'note' => 'nullable|string',
        ];
    }
}
