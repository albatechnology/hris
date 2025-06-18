<?php

namespace App\Http\Requests\Api\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class TodayScheduleRequest extends FormRequest
{
    


    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $date = new \DateTime(str_replace(' ', '+', $this->date));
        $this->merge([
            'date' => $date->format('Y-m-d H:i:s'),
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
            'date' => 'nullable|date_format:Y-m-d H:i:s',
            'include' => 'nullable|string',
        ];
    }
}
