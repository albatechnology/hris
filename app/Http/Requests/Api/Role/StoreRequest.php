<?php

namespace App\Http\Requests\Api\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'group_id' => 'nullable|exists:groups,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,name',
        ];
    }
}
