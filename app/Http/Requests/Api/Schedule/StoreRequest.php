<?php

namespace App\Http\Requests\Api\Schedule;

use App\Enums\ApprovalStatus;
use App\Enums\ScheduleType;
use App\Models\Shift;
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
            'is_overide_national_holiday' => $this->toBoolean($this->is_overide_national_holiday),
            'is_overide_company_holiday' => $this->toBoolean($this->is_overide_company_holiday),
            'is_include_late_in' => $this->toBoolean($this->is_include_late_in),
            'is_include_early_out' => $this->toBoolean($this->is_include_early_out),
            'is_flexible' => $this->toBoolean($this->is_flexible),
            'is_generate_timeoff' => $this->toBoolean($this->is_generate_timeoff),
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
            'effective_date' => 'required|date',
            'is_overide_national_holiday' => 'nullable|boolean',
            'is_overide_company_holiday' => 'nullable|boolean',
            'is_include_late_in' => 'nullable|boolean',
            'is_include_early_out' => 'nullable|boolean',
            'is_flexible' => 'nullable|boolean',
            'is_generate_timeoff' => 'nullable|boolean',
            'is_need_approval' => 'nullable|boolean',
            'approval_status' => ['nullable', Rule::enum(ApprovalStatus::class)],

            'shifts' => 'nullable|array',
            // 'shifts.*.id' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found', fn($q) => $q->orWhereNull('company_id'))],
            'shifts.*.id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (Shift::tenanted()->where('id', $value)->doesntExist()) {
                        if (Shift::whereNull('company_id')->where('id', $value)->first(['id'])) return;
                        $fail("Shift {$value} not found");
                    };
                }
            ],
            'shifts.*.order' => 'required|integer',
        ];
    }
}
