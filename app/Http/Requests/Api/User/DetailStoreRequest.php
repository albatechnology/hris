<?php

namespace App\Http\Requests\Api\User;

use App\Enums\BloodType;
use App\Enums\ClothesSize;
use App\Enums\EmploymentStatus;
use App\Enums\JobLevel;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
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
            'address' => 'nullable|string',
            'address_ktp' => 'nullable|string',
            'job_position' => 'nullable|string',
            'job_level' => ['nullable', Rule::enum(JobLevel::class)],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'passport_no' => 'nullable|string',
            'passport_expired' => 'nullable|date',
            'birth_place' => 'nullable',
            'birthdate' => 'nullable|date_format:Y-m-d',
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'batik_size' => ['nullable', Rule::enum(ClothesSize::class)],
            'tshirt_size' => ['nullable', Rule::enum(ClothesSize::class)],
        ];
    }
}
