<?php

namespace App\Http\Requests\Api\User;

use App\Enums\BloodType;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Enums\UserType;
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
        // dd($this->all());
        return [
            'group_id' => 'nullable|exists:groups,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'manager_id' => 'nullable|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'type' => ['required', Rule::enum(UserType::class)],
            'nik' => 'nullable',
            'phone' => 'nullable',
            'birth_place' => 'nullable',
            'birthdate' => 'nullable',
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'required|exists:roles,id',
        ];
    }
}
