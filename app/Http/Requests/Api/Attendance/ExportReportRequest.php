<?php

namespace App\Http\Requests\Api\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ExportReportRequest extends FormRequest
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
                'start_date' => !empty($this->filter['start_date']) ? $this->filter['start_date'] : date('Y-m-01'),
                'end_date' => !empty($this->filter['end_date']) ? $this->filter['end_date'] : date('Y-m-t'),
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
            'filter' => 'required|array',
            'filter.start_date' => 'required|date',
            'filter.end_date' => 'required|date',
        ];
    }
}
