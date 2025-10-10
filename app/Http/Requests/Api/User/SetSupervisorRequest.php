<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class SetSupervisorRequest extends FormRequest
{
    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_additional_supervisor' => $this->is_additional_supervisor ?? false,
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
            'is_additional_supervisor' => 'required|boolean',
            'data' => 'required|array',
            'data.*.supervisor_id' => 'required|exists:users,id',
            'data.*.order' => 'required|integer',
        ];
    }
}
