<?php

namespace App\Http\Requests\Api\Incident;

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
            'company_id' => 'required|exists:companies,id',
            'incident_type_id' => 'required|exists:incident_types,id',
            'description' => 'required|string',
            'file' => 'nullable|array',
            'file.*' => 'required|mimes:' . config('app.file_mimes_types'),
        ];
    }
}
