<?php

namespace App\Http\Requests\Api\Subscription;

use App\Models\Subscription;
use App\Rules\CompanyTenantedRule;
use Closure;
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
            'name' => 'required|string',
            'email' => 'required|unique:users,email',
            'phone' => 'required|string|max:16',
            'company_name' => 'required|string|max:50',
            'company_address' => 'required|string|max:150',
        ];
    }
}
