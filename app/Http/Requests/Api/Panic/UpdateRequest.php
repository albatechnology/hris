<?php

namespace App\Http\Requests\Api\Panic;

use App\Enums\PanicStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'solved_by_id' => auth('sanctum')->id(),
            'solved_at' => now()
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
            'status' => ['required', Rule::enum(PanicStatus::class)],
            'solved_by_id' => ['nullable', 'integer'],
            'solved_at' => ['required'],
            'solved_lat' => ['nullable', 'string'],
            'solved_lng' => ['nullable', 'string'],
            'solved_description' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['required', 'mimes:' . config('app.file_mimes_types')],
        ];
    }
}
