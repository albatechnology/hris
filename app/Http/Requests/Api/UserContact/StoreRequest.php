<?php

namespace App\Http\Requests\Api\UserContact;

use App\Enums\ContactType;
use App\Enums\Gender;
use App\Enums\RelationshipType;
use App\Enums\Religion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    protected function prepareForValidation()
    {
        if($this->relationship !== RelationshipType::SPOUSE->value){
            $this->merge([
                'is_working' => null,
                'is_disabled' => null,
                'tax_identification_no' => null,
            ]);
        }
    }
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ContactType::class)],
            'name' => 'required|string',
            'phone' => 'required',
            'relationship' => ['required', Rule::enum(RelationshipType::class)],
            'email' => 'nullable|email',
            'id_number' => ['nullable','string'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'job' => 'nullable|string',
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'birthdate' => 'nullable|date',
            'is_working' => Rule::when(
                $this->relationship === RelationshipType::SPOUSE->value,
                ['required','boolean'],
                ['nullable','boolean']
            ),
            'is_disabled' => Rule::when(
                $this->relationship === RelationshipType::SPOUSE->value,
                ['required','boolean'],
                ['nullable','boolean']
            ),
            'tax_identification_no' => Rule::when(
                $this->relationship === RelationshipType::SPOUSE->value,
                ['required','string'],
                ['nullable','string']
            ),
        ];
    }

    public function messages()
    {
        return [
            'id_number.regex' => 'Invalid Identity Number Format',
        ];
    }
}
