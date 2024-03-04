<?php

namespace App\Http\Requests\Api\UserEducation;

use App\Enums\EducationLevel;
use App\Enums\EducationType;
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
            'type' => ['required', Rule::enum(EducationType::class)],
            'level' => ['nullable', Rule::enum(EducationLevel::class)],
            'name' => 'nullable|string',
            'institution_name' => 'required|string',
            'majors' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'score' => 'nullable|string',
            'fee' => 'nullable|numeric',
            'file' => 'nullable|mimes:' . config('app.file_mimes_types')
        ];
    }
}
