<?php

namespace App\Http\Requests\Api\RunPayroll;

use App\Enums\RateType;
use App\Models\RunPayroll;
use App\Models\RunPayrollUser;
use App\Models\RunPayrollUserComponent;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserComponentRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!$this->run_payroll->tenanted()->exists()) return false;

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_components' => ['required', 'array'],
            'user_components.*.id' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (!RunPayrollUserComponent::findOrFail($value)->runPayrollUser?->runPayroll()?->tenanted()->exists()) $fail("Invalid data access for {$attribute}");
            },],
            'user_components.*.amount' => ['required', 'numeric'],
        ];
    }
}
