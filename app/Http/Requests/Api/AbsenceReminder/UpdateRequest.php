<?php

namespace App\Http\Requests\Api\AbsenceReminder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
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
            'minutes_repeat' => $this->minutes_repeat && $this->minutes_repeat > 5 ? $this->minutes_repeat : 5,
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
            'minutes_before' => 'required|numeric|min:0',
            'minutes_repeat' => 'required|numeric|min:5',
        ];
    }
}
