<?php

namespace App\Http\Requests\Api\PatrolLocation;

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
        dd($this->route('patrolId'));
        return [
            'patrol_id' => 'required|exists:clients,id',
            'client_location_id' => 'required|exists:clients,id',
        ];
    }
}
