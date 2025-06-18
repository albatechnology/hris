<?php

namespace App\Http\Requests\Api\Overtime;

use Illuminate\Foundation\Http\FormRequest;

class UserSettingRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'overtime_id' => 'required|integer|exists:overtimes,id',
        ];
    }
}
