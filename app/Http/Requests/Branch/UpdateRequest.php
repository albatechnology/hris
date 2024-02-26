<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string',
            'country' => 'nullable|string',
            'province' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'address' => 'nullable|string',
        ];
    }
}
