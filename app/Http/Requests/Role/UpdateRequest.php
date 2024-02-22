<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize() : bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules() : array
    {
        return [
            'group_id' => 'nullable|exists:groups,id',
            'name' => 'required|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ];
    }
}
