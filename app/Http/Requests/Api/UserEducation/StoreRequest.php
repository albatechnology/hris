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
            'level' => ['required', Rule::enum(EducationLevel::class)],
            'institution_name' => 'required|string',
            'majors' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'score' => 'required|string',
            'fee' => 'required|numeric',
        ];
    }
}
