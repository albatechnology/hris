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
            'no_ktp' => 'required|string',
            'kk_no' => 'nullable|string',
            'postal_code' => 'required|string',
            'address' => 'nullable|string',
            'address_ktp' => 'required|string',
            'job_position' => 'required|string',
            'job_level' => ['nullable', Rule::enum(JobLevel::class)],
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'passport_no' => 'nullable|string',
            'passport_expired' => 'nullable|date_format:Y-m-d',
            'birth_place' => 'required',
            'birthdate' => 'required|date_format:Y-m-d',
            'marital_status' => ['required', Rule::enum(MaritalStatus::class)],
            'blood_type' => ['required', Rule::enum(BloodType::class)],
            'rhesus' => ['nullable', 'string'],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'batik_size' => ['nullable', Rule::enum(ClothesSize::class)],
            'tshirt_size' => ['nullable', Rule::enum(ClothesSize::class)],
        ];
    }
}
