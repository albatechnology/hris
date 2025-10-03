<?php

namespace App\Http\Requests\Api\PayrollProrate;

use App\Enums\ProrateSetting;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $prorateSetting = ProrateSetting::from($this->prorate_setting);
        $this->merge([
            'prorate_custom_working_day' => $prorateSetting->hasProrateCustomWorkingDay() ? $this->prorate_custom_working_day : null,
            'prorate_national_holiday_as_working_day' => $prorateSetting->hasCountNationalHolidayAsWorkingDay() ? $this->toBoolean($this->prorate_national_holiday_as_working_day) : false,
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
            'prorate_setting' => ['required', Rule::enum(ProrateSetting::class)],
            'prorate_custom_working_day' => 'nullable|integer|min:0|max:31',
            'prorate_national_holiday_as_working_day' => 'nullable|boolean',
        ];
    }
}
