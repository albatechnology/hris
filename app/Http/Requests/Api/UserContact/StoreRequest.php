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
            'type' => ['required', Rule::enum(ContactType::class)],
            'name' => 'required',
            'id_number' => 'required',
            'relationship' => ['required', Rule::enum(RelationshipType::class)],
            'gender' => ['required', Rule::enum(Gender::class)],
            'job' => 'required',
            'religion' => ['required', Rule::enum(Religion::class)],
            'birthdate' => 'required|date',
            'email' => 'required|email',
            'phone' => 'required',
        ];
    }
}
