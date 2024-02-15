<?php

namespace App\Http\Requests\Api\OvertimeRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'shift_id' => 'required|exists:shifts,id',
            'overtime_id' => 'required|exists:overtimes,id',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'note' => 'nullable|string',
        ];
    }
}
