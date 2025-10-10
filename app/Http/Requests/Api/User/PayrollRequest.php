<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'month' => $this->month ?? date('m'),
            'year' => $this->year ?? date('Y'),
            'is_json' => $this->is_json ?? false,
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
            'month' => 'required|date_format:m',
            'year' => 'required|date_format:Y',
            'is_json' => 'nullable|boolean',
        ];
    }
}
