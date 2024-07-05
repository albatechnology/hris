<?php

namespace App\Http\Requests\Api\UserTransfer;

use App\Enums\EmploymentStatus;
use App\Enums\TransferType;
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
            'is_notify_manager' => $this->toBoolean($this->is_notify_manager),
            'is_notify_user' => $this->toBoolean($this->is_notify_user),
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
            'type' => ['required', Rule::enum(TransferType::class)],
            'effective_date' => 'required|date',
            'employment_status' => ['nullable', Rule::enum(EmploymentStatus::class)],
            'approval_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string',
            'is_notify_manager' => 'nullable|boolean',
            'is_notify_user' => 'nullable|boolean',
            'file' => 'required|mimes:' . config('app.file_mimes_types'),

            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'required|exists:branches,id',

            'positions' => 'nullable|array',
            'positions.*.position_id' => 'required|exists:positions,id',
            'positions.*.department_id' => 'required|exists:departments,id',
        ];
    }
}
