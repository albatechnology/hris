<?php

namespace App\Http\Requests\Api\GuestBook;

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
            // 'is_check_out' => 'nullable|boolean',
            // 'name' => 'required|string|min:2|max:100',
            // 'address' => 'required|string|min:2|max:200',
            // 'location_destination' => 'required|string|min:2|max:200',
            // 'room' => 'required|string|min:2|max:200',
            // 'name_destination' => 'required|string|min:2|max:200',
            // 'description' => 'required|string|min:2|max:200',
            // 'vehicle_number' => 'nullable|string|min:2|max:50',
            'files' => 'required|array',
            'files.*' => 'required|mimes:' . config('app.image_mimes_types'),
        ];
    }
}
