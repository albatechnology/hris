<?php

namespace App\Http\Requests\Api\TaskRequest;

use Illuminate\Foundation\Http\FormRequest;

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
            'task_hour_id' => 'required|exists:task_hours,id',
            'start_at' => 'required|date_format:Y-m-d H:i',
            'end_at' => 'required|date_format:Y-m-d H:i',
            'note' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'required|mimes:' . config('app.file_mimes_types'),
        ];
    }
}
