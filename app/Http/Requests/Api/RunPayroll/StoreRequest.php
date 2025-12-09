<?php

namespace App\Http\Requests\Api\RunPayroll;

use App\Http\DTO\Payroll\RunPayrollDTO;
use App\Models\Branch;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    protected function prepareForValidation()
    {
        $branchId = $this->branch_id ?? null;
        $companyId = $this->company_id ?? null;
        if ($branchId) {
            $companyId = Branch::tenanted()->where('id', $branchId)->firstOrFail(['company_id'])->company_id;
        }

        $this->merge([
            'branch_id' => $branchId,
            'company_id' => $companyId,
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
            'branch_id' => Rule::requiredIf(config('app.name') === "SYNTEGRA"),
            'company_id' => ['required', new CompanyTenantedRule()],
            'period' => ['required', 'string', function (string $attr, string $value, Closure $fail) {
                // $runPayroll = RunPayroll::where('period', $value)->exists();
                // if ($runPayroll) {
                //     $fail('Cannot run payroll in the same period');
                // }
            }],
            'payment_schedule' => 'required|date',
            'user_ids' => [
                'nullable',
                'string',
                function (string $attr, string $value, Closure $fail) {
                    collect(explode(',', $value))->each(function ($id) use ($fail) {
                        $user = User::tenanted()->where('company_id', $this->company_id)->select('id', 'resign_date')->firstWHere('id', $id);

                        if (!$user) {
                            $fail('The selected user ids is invalid (' . $id . ')');
                        }
                    });
                },
            ],
        ];
    }

    public function toDTO(): RunPayrollDTO
    {
        return RunPayrollDTO::fromArray($this->validated());
    }
}
