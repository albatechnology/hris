<?php

namespace App\Http\Requests\Api\LiveAttendanceLocation;

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
            'locations' => 'required|array',
            'locations.*.name' => 'required|string',
            'locations.*.radius' => 'nullable|integer',
            'locations.*.lat' => 'required|string',
            'locations.*.lng' => 'required|string',
        ];
    }
}
