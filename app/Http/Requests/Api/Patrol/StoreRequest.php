<?php

namespace App\Http\Requests\Api\Patrol;

use Illuminate\Foundation\Http\FormRequest;

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
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'description' => 'nullable|string',

            'users' => 'required|array',
            'users.*.id' => 'required|integer|exists:users,id',
            'users.*.schedules' => 'required|array',
            'users.*.schedules.*.id' => 'required|exists:schedules,id',

            'locations' => 'required|array',
            // 'locations.*.client_location_id' => 'required|exists:client_locations,id',
            'locations.*.client_location_id' => 'required|exists:client_locations,id',
            'locations.*.tasks' => 'required|array',
            'locations.*.tasks.*.name' => 'required|string',
            'locations.*.tasks.*.description' => 'required|string',
        ];
    }
}
