<?php

namespace App\Http\Requests\Api\TimeoffQuota;

use App\Models\Branch;
use App\Models\Company;
use App\Models\TimeoffPolicy;
use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UserTimeoffQuota extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter.company_id' => ['nullable', new CompanyTenantedRule(Company::class, 'Company not found')],
            'filter.branch_id' => ['nullable', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            'filter.timeoff_policy_ids' => [
                'nullable',
                'string',
                function (string $attr, string $value, Closure $fail) {
                    collect(explode(',', trim($value)))->each(function ($id) use ($fail) {
                        $timeoffPolicy = TimeoffPolicy::tenanted()->select('id')->firstWHere('id', $id);

                        if (!$timeoffPolicy) {
                            $fail('The selected timeoff policy ids is invalid (' . $id . ')');
                        }
                    });
                },
            ],
            'filter.name' => 'nullable|string',
        ];
    }
}
