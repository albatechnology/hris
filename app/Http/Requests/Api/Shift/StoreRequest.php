<?php

namespace App\Http\Requests\Api\Shift;

use App\Enums\ScheduleType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Rules\CompanyTenantedRule;
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
        $this->merge([
            'clock_in_dispensation' => $this->clock_in_dispensation ?? 0,
            'clock_out_dispensation' => $this->clock_out_dispensation ?? 0,
            'time_dispensation' => $this->time_dispensation ?? 0,
            'is_enable_validation' => $this->toBoolean($this->is_enable_validation),
            'is_enable_grace_period' => $this->toBoolean($this->is_enable_grace_period),
            'is_show_in_request' => $this->toBoolean($this->is_show_in_request),
            'is_show_in_request_for_all' => $this->toBoolean($this->is_show_in_request_for_all),
            // 'is_enable_auto_overtime' => $this->toBoolean($this->is_enable_auto_overtime),
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
            'company_id' => ['required', new CompanyTenantedRule],
            'type' => ['nullable', Rule::enum(ScheduleType::class)],
            'name' => 'required|string',
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'color' => 'nullable|string|min:4|max:7',
            'description' => 'nullable|string',
            'is_enable_validation' => 'nullable|boolean',
            'clock_in_min_before' => 'nullable|integer',
            'clock_out_max_after' => 'nullable|integer',
            'is_enable_grace_period' => 'nullable|boolean',
            'clock_in_dispensation' => 'integer|integer',
            'clock_out_dispensation' => 'integer|integer',
            'time_dispensation' => 'integer|integer',
            'is_show_in_request' => 'nullable|boolean',
            'is_show_in_request_for_all' => 'nullable|boolean',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'department_ids' => 'nullable|array',
            'department_ids.*' => ['required', new CompanyTenantedRule(Department::class, 'Department not found')],
            'position_ids' => 'nullable|array',
            'position_ids.*' => ['required', new CompanyTenantedRule(Position::class, 'Position not found')],
            // 'is_enable_auto_overtime' => 'nullable|boolean',
            // 'overtime_before' => 'nullable|date_format:H:i',
            // 'overtime_after' => 'nullable|date_format:H:i',
        ];
    }
}
