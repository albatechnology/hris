<?php

namespace App\Http\Requests\Api\UserPatrolBatch;

use App\Models\Patrol;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

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
            // 'user_id' => ['nullable', new CompanyTenantedRule(User::class, 'User not found')],
            'patrol_id' => ['required', new CompanyTenantedRule(Patrol::class, 'Patrol not found')],
        ];
    }
}
