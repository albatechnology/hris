<?php

namespace App\Http\Requests\Api\Panic;

use App\Models\Client;
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
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'client_id' => $this->client_id ?? auth('sanctum')->user()->client_id
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', new CompanyTenantedRule(Client::class, 'Client not found')],
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ];
    }
}
