<?php

namespace App\Http\Requests\Api\Task;

use App\Enums\WorkingPeriod;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $additionalValidation = [];
        if ($this->task) {
            $additionalValidation = [
                'hours.*.id' => 'required|exists:task_hours,id'
            ];
        }

        return [
            'company_id' => ['required', new CompanyTenantedRule],
            'name' => 'required|string',
            'min_working_hour' => 'nullable|integer',
            'working_period' => ['required', Rule::enum(WorkingPeriod::class)],
            'description' => 'nullable|string',
            'weekday_overtime_rate' => 'nullable|numeric',
            'weekend_overtime_rate' => 'nullable|numeric',

            'hours' => 'nullable|array',
            'hours.*.name' => 'required|string',
            'hours.*.min_working_hour' => 'required|integer',
            'hours.*.max_working_hour' => 'required|integer',
            ...$additionalValidation
        ];
    }
}
