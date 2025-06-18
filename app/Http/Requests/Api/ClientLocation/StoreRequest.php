<?php

namespace App\Http\Requests\Api\ClientLocation;

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
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string',
            'lat' => 'required|string',
            'lng' => 'required|string',
            'address' => 'required|string',
            'description' => 'nullable|string',
        ];
    }
}
