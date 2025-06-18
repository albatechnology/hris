<?php

namespace App\Http\Requests\Timeoff;

use App\Enums\TimeoffRequestType;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'user_id' => 'nullable|exists:users,id',
            'timeoff_policy_id' => 'required|exists:timeoff_policies,id',
            'request_type' => ['required', Rule::enum(TimeoffRequestType::class)],
            'start_at' => 'required',
            'end_at' => 'required',
            'reason' => 'nullable|string',
        ];
    }
}
