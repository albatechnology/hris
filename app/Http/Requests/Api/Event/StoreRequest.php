<?php

namespace App\Http\Requests\Api\Event;

use App\Enums\EventType;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'company_id' => ['required', new CompanyTenantedRule()],
            'name' => 'required|string',
            'type' => ['required', Rule::enum(EventType::class)],
            'start_at' => 'required|date_format:Y-m-d',
            'end_at' => 'required|date_format:Y-m-d',
            'is_public' => 'required|boolean',
            'is_send_email' => 'required|boolean',
            'description' => 'required|string',
        ];
    }
}
