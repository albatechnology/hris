<?php

namespace App\Http\Requests\Api\Attendance;

use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAttendanceRequest extends FormRequest
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
            'is_approved' => $this->toBoolean($this->is_approved),
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
            'is_approved' => 'required|boolean',
            'approved_at' => 'required',
            'approved_by' => 'required',
        ];
    }
}
