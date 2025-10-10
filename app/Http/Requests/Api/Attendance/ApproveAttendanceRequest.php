<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\ApprovalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveAttendanceRequest extends FormRequest
{
    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'approved_at' => now(),
            'approved_by' => auth('sanctum')->id(),
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
            'approved_at' => 'required',
            'approved_by' => 'required',
        ];
    }
}
