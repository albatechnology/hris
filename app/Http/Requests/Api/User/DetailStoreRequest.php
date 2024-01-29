<?php

namespace App\Http\Requests\Api\User;

use App\Enums\EmploymentStatus;
use App\Enums\JobLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetailStoreRequest extends FormRequest
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
            'no_ktp' => 'nullable|string',
            'kk_no' => 'nullable|string',
            'job_position' => 'nullable|string',
            'job_level' => ['nullable', Rule::enum(JobLevel::class)],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'join_date' => 'nullable|date',
            'sign_date' => 'nullable|date',
            'passport_no' => 'nullable|string',
            'passport_expired' => 'nullable|date',
            'address' => 'nullable|string',
        ];
    }
}
