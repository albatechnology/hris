<?php

namespace App\Http\Requests\Api\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
            'filter' => [
                'month' => $this->filter['month'] && !empty($this->filter['month']) ? $this->filter['month'] : date('m'),
                'year' => $this->filter['year'] && !empty($this->filter['year']) ? $this->filter['year'] : date('Y'),
                ...($this->filter ?? [])
            ],
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
            'filter' => 'nullable|array',
            'sort' => 'nullable|string',
            'include' => 'nullable|string',
        ];
    }
}
