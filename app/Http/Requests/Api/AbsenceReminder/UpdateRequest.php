<?php

namespace App\Http\Requests\Api\AbsenceReminder;

use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use RequestToBoolean;

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
            'minutes_before' => $this->minutes_before && $this->minutes_before > 5 ? $this->minutes_before : 5,
            'minutes_repeat' => $this->minutes_repeat && $this->minutes_repeat > 5 ? $this->minutes_repeat : 5,
        ]);

        if (isset($this->is_active)) {
            $this->merge([
                'is_active' => $this->toBoolean($this->is_active),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_active' => 'nullable|boolean',
            'minutes_before' => 'required|numeric|min:0',
            'minutes_repeat' => 'required|numeric|min:5',
        ];
    }
}
