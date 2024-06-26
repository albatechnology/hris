<?php

namespace App\Http\Requests\Api\OvertimeRequest;

use App\Models\Schedule;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

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
            'is_after_shift' => $this->toBoolean($this->is_after_shift),
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
            'user_id' => 'required|exists:users,id',
            'schedule_id' => ['required', new CompanyTenantedRule(Schedule::class, 'Schedule not found')],
            'shift_id' => 'required|exists:shifts,id',
            // 'type' => ['required', Rule::enum(OvertimeRequestType::class)],
            'date' => 'required|date',
            'is_after_shift' => 'required|boolean',
            'duration' => 'required|date_format:H:i',
            // 'start_at' => 'required|date_format:Y-m-d H:i',
            // 'end_at' => 'required|date_format:Y-m-d H:i',
            'note' => 'nullable|string',
        ];
    }
}
