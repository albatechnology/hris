<?php

namespace App\Http\Requests\Api\UserPatrolBatch;

use App\Models\Patrol;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', new CompanyTenantedRule(User::class, 'User not found')],
            'patrol_id' => ['required', new CompanyTenantedRule(Patrol::class, 'Patrol not found')],
        ];
    }
}
