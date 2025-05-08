<?php

namespace App\Http\Requests\Api\Patrol;

use App\Models\Client;
use App\Models\ClientLocation;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
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
            'client_id' => ['required', new CompanyTenantedRule(Client::class, 'Client not found')],
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'description' => 'nullable|string',

            'hours' => 'required|array',
            'hours.*.start_hour' => 'required|date_format:H:i',
            'hours.*.end_hour' => 'required|date_format:H:i',
            'hours.*.description' => 'nullable|string',

            'users' => 'required|array',
            'users.*' => ['required', 'integer', new CompanyTenantedRule(User::class, 'User not found')],
            // 'users.*.id' => 'required|integer|exists:users,id',
            // 'users.*.schedules' => 'required|array',
            // 'users.*.schedules.*.id' => 'required|exists:schedules,id',

            'locations' => 'required|array',
            'locations.*.id' => ['nullable', 'exists:patrol_locations,id'],
            'locations.*.client_location_id' => ['required', new CompanyTenantedRule(ClientLocation::class, 'Location not found')],
            // 'locations.*.client_location_id' => 'required|exists:client_locations,id',
            'locations.*.tasks' => 'required|array',
            'locations.*.tasks.*.id' => ['nullable', 'exists:patrol_tasks,id'],
            'locations.*.tasks.*.name' => 'required|string',
            'locations.*.tasks.*.description' => 'required|string',
        ];
    }
}
