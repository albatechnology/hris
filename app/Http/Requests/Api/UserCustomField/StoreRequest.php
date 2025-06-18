<?php

namespace App\Http\Requests\Api\UserCustomField;

use App\Models\CustomField;
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
        $customField = CustomField::findOrFail($this->custom_field_id);

        return [
            'custom_field_id' => 'required|string',
            'value' => $customField->type->getValidationRules(),
        ];
    }
}
