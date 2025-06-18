<?php

namespace App\Http\Requests\Api\PayrollProrate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prorate_setting' => 'required',
            'is_count_national_holiday_as_working_day' => 'nullable|boolean',
        ];
    }
}
