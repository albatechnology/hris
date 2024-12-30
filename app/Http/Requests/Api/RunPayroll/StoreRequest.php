<?php

namespace App\Http\Requests\Api\RunPayroll;

use App\Enums\FormulaComponentEnum;
use App\Enums\RateType;
use App\Models\RunPayroll;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            'period' => ['required', 'string', function (string $attr, string $value, Closure $fail) {
                // $runPayroll = RunPayroll::where('period', $value)->exists();
                // if ($runPayroll) {
                //     $fail('Cannot run payroll in the same period');
                // }
            }],
            'payment_schedule' => 'required|date',
            'user_ids' => [
                'required',
                'string',
                fn(string $attr, string $value, Closure $fail) => collect(explode(',', $value))->map(fn($userId) => User::find($userId) ?? $fail('The selected user ids is invalid (' . $userId . ')')),
            ],
        ];
    }
}
