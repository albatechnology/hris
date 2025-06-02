<?php

namespace App\Http\Requests\Api\Event;

use Illuminate\Foundation\Http\FormRequest;

class CalendarRequest extends FormRequest
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
        $date = null;
        if(!empty($this->filter['date'])){
            $date = date('d', strtotime(sprintf('2024-01-%s', $this->filter['date'])));
        }
        $this->merge([
            'filter' => [
                'date' => $date,
                'month' => !empty($this->filter['month']) ? date('m', strtotime(sprintf('2024-%s-01', $this->filter['month']))) : date('m'),
                'year' => !empty($this->filter['year']) ? date('Y', strtotime(sprintf('%s-01-01', $this->filter['year']))) : date('Y'),
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
            'filter.date' => 'nullable|date_format:d',
            'filter.month' => 'required|date_format:m',
            'filter.year' => 'required|date_format:Y',
            'filter.branch_id' => 'nullable',
            // 'filter.client_id' => 'nullable',
            // 'sort' => 'nullable|string',
            // 'include' => 'nullable|string',
        ];
    }
}
