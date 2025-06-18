<?php

namespace App\Http\Requests\Api\RequestShift;

use App\Models\Shift;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'new_shift_id' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found', fn($q) => $q->orWhereNull('company_id'))],
            'date' => 'required|date',
            'description' => 'nullable|string',
        ];
    }
}
