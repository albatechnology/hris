<?php

namespace App\Http\Requests\Api\Schedule;

use App\Models\Shift;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleShiftRequest extends FormRequest
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
            'shifts' => 'nullable|array',
            'shifts.*.id' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found')],
            'shifts.*.order' => 'required|integer'
        ];
    }
}
