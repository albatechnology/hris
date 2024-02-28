<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\AttendanceType;
use App\Models\Schedule;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'month' => $this->filter['month'] ?? date('m'),
                'year' => $this->filter['year'] ?? date('Y'),
                ...$this->filter
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
