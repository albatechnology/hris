<?php

namespace App\Http\Requests\Api\Announcement;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_send_email' => $this->toBoolean($this->is_send_email),
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
            'company_id' => ['required', new CompanyTenantedRule],
            'subject' => 'required|string',
            'content' => 'required|string',
            'is_send_email' => 'required|boolean',

            'branch_ids' => ['nullable', fn(string $attr, string $value, Closure $fail) => collect(explode(',', $value))->map(fn($branchId) => Branch::findTenanted($branchId) ?? $fail('The selected branch ids is invalid (' . $branchId . ')'))],
            'position_ids' => ['nullable', fn(string $attr, string $value, Closure $fail) => collect(explode(',', $value))->map(fn($positionIds) => Position::findTenanted($positionIds) ?? $fail('The selected position ids is invalid (' . $positionIds . ')'))],
            'department_ids' => ['nullable', fn(string $attr, string $value, Closure $fail) => collect(explode(',', $value))->map(fn($departmentIds) => Department::findTenanted($departmentIds) ?? $fail('The selected department ids is invalid (' . $departmentIds . ')'))],
            'file' => 'nullable|mimes:' . config('app.file_mimes_types'),
            // 'job_levels' => ['nullable', fn(string $attr, string $value, Closure $fail) => collect(explode(',', $value))->map(fn($jobLevel) => JobLevel::getValue($jobLevel) ?? $fail('The selected job levels is invalid (' . $jobLevel . ')'))],
        ];
    }
}
