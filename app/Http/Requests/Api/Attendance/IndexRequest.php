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
                ...($this->filter ?? []),
                // 'month' => !empty($this->filter['month']) ? date('m', strtotime(sprintf('2024-%s-01', $this->filter['month']))) : date('m'),
                // 'year' => !empty($this->filter['year']) ? date('Y', strtotime(sprintf('%s-01-01', $this->filter['year']))) : date('Y'),
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
            'filter.month' => 'nullable|date_format:m',
            'filter.year' => 'nullable|date_format:Y',
            'sort' => 'nullable|string',
            'include' => 'nullable|string',
        ];
    }
}
