<?php

namespace App\Http\Requests\Api\AdvancedLeaveRequest;

use App\Models\User;
use App\Services\AdvancedLeaveRequestService;
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
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->user_id ? $this->user_id : auth('sanctum')->id(),
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
            'user_id' => 'required|exists:users,id',
            'amount' => ['required', 'numeric', 'gt:0', function (string $attribute, mixed $value, Closure $fail) {
                $availableDays = AdvancedLeaveRequestService::getAvailableDays(User::findOrFail($this->user_id));
                if ($value > $availableDays) $fail("The {$attribute} field must be less than or equal to {$availableDays} days.");
            }],
        ];
    }
}
