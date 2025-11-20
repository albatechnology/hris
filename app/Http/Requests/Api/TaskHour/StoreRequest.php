<?php

namespace App\Http\Requests\Api\TaskHour;

use App\Models\Task;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required', new CompanyTenantedRule(Task::class, "Task not found")],
            'name' => 'required|string',
            'min_working_hour' => 'required|integer',
            // 'max_working_hour' => 'required|integer',
            'hours' => 'nullable|array',
            'hours.*.name' => 'required|string',
            'hours.*.clock_in' => 'required|date_format:H:i',
            'hours.*.clock_out' => 'required|date_format:H:i',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'required|exists:users,id',
        ];
    }
}
