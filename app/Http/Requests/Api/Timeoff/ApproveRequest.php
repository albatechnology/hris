<?php

namespace App\Http\Requests\Api\Timeoff;

use App\Enums\TimeoffRequestType;
use App\Traits\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveRequest extends FormRequest
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
            'is_approved' => is_null($this->is_approved) ? null : $this->toBoolean($this->is_approved),
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
            'is_approved' => 'nullable|boolean',
        ];
    }
}
