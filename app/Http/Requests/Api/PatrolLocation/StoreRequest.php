<?php

namespace App\Http\Requests\Api\PatrolLocation;

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
            'branch_location_id' => 'required|exists:branch_locations,id',
            'description' => 'nullable',
        ];
    }
}
