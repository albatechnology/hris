<?php

namespace App\Http\Requests\Api;

use App\Enums\ApprovalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewApproveRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth('sanctum')->id(),
            'approved_at' => now()
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
            'approval_status' => ['required', Rule::enum(ApprovalStatus::class)],
            'user_id' => 'required',
            'approved_at' => 'required',
        ];
    }
}
