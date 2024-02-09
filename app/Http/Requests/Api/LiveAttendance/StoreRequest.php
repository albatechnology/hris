<?php

namespace App\Http\Requests\Api\LiveAttendance;

use App\Traits\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $isFlexible = $this->toBoolean($this->is_flexible);
        $data = [
            'is_flexible' => $isFlexible,
        ];

        if ($isFlexible) {
            $data = [
                'is_flexible' => $isFlexible,
                'locations' => null,
            ];
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'is_flexible' => 'required|boolean',
            'locations' => 'nullable|array',
            'locations.*.name' => 'required|string',
            'locations.*.radius' => 'nullable|integer',
            'locations.*.lat' => 'required|string',
            'locations.*.lng' => 'required|string',
        ];
    }
}
