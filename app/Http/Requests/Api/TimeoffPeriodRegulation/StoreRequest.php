<?php

namespace App\Http\Requests\Api\TimeoffPeriodRegulation;

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
            'min_working_month' => 'required|integer',
            'max_working_month' => 'required|integer',
            'month' => ['required', 'array', function (string $attribute, mixed $value, \Closure $fail) {
                if (count($value) <> 12) $fail('The months must be 12 month.');
            }],
            'month.01' => 'required|integer',
            'month.02' => 'required|integer',
            'month.03' => 'required|integer',
            'month.04' => 'required|integer',
            'month.05' => 'required|integer',
            'month.06' => 'required|integer',
            'month.07' => 'required|integer',
            'month.08' => 'required|integer',
            'month.09' => 'required|integer',
            'month.10' => 'required|integer',
            'month.11' => 'required|integer',
            'month.12' => 'required|integer',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.01.required' => 'The January field is required.',
            'month.02.required' => 'The February field is required.',
            'month.03.required' => 'The March field is required.',
            'month.04.required' => 'The April field is required.',
            'month.05.required' => 'The May field is required.',
            'month.06.required' => 'The June field is required.',
            'month.07.required' => 'The July field is required.',
            'month.08.required' => 'The August field is required.',
            'month.09.required' => 'The September field is required.',
            'month.10.required' => 'The October field is required.',
            'month.11.required' => 'The November field is required.',
            'month.12.required' => 'The December field is required.',

            'month.01.integer' => 'The January field must be an integer.',
            'month.02.integer' => 'The February field must be an integer.',
            'month.03.integer' => 'The March field must be an integer.',
            'month.04.integer' => 'The April field must be an integer.',
            'month.05.integer' => 'The May field must be an integer.',
            'month.06.integer' => 'The June field must be an integer.',
            'month.07.integer' => 'The July field must be an integer.',
            'month.08.integer' => 'The August field must be an integer.',
            'month.09.integer' => 'The September field must be an integer.',
            'month.10.integer' => 'The October field must be an integer.',
            'month.11.integer' => 'The November field must be an integer.',
            'month.12.integer' => 'The December field must be an integer.',
        ];
    }
}
