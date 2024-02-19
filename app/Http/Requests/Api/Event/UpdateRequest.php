<?php

namespace App\Http\Requests\Api\Overtime;

use App\Enums\EventType;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'company_id' => ['nullable', new CompanyTenantedRule()],
            'name' => 'required|string',
            'type' => ['nullable', Rule::enum(EventType::class)],
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'is_public' => 'required|boolean',
            'is_send_email' => 'required|boolean',
            'description' => 'required|string',
        ];
    }
}
