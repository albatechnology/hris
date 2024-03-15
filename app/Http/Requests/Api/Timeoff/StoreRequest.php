<?php

namespace App\Http\Requests\Api\Timeoff;

use App\Enums\TimeoffRequestType;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $this->merge([
            'is_advanced_leave' => $this->toBoolean($this->is_advanced_leave),
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
            'user_id' => 'nullable|exists:users,id',
            'timeoff_policy_id' => 'required|exists:timeoff_policies,id',
            'request_type' => ['required', Rule::enum(TimeoffRequestType::class)],
            'start_at' => 'required|date_format:Y-m-d H:i',
            'end_at' => 'required|date_format:Y-m-d H:i',
            'reason' => 'nullable|string',
            'delegate_to' => 'nullable|exists:users,id',
            'is_advanced_leave' => 'nullable|boolean',
        ];
    }
}
