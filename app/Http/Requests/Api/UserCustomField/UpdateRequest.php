<?php

namespace App\Http\Requests\Api\UserCustomField;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value' => $this->custom_field->customField->type->getValidationRules(),
        ];
    }
}
