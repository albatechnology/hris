<?php

namespace App\Http\Requests\Api\TaskHour;

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
            'name' => 'required|string',
            'min_working_hour' => 'required|integer',
            'max_working_hour' => 'required|integer',
            'hours' => 'nullable|array',
            'hours.*.name' => 'required|string',
            'hours.*.clock_in' => 'required|date_format:H:i',
            'hours.*.clock_out' => 'required|date_format:H:i',
        ];
    }
}
