<?php

namespace App\Http\Requests\Api\JobLevel;

use App\Models\JobLevel;
use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => [new CompanyTenantedRule()],
            'name' => 'required|string',
            'code' => [
                'required',
                'string',
                function (mixed $attribute, string $value, Closure $fail) {
                    if (JobLevel::tenanted()->where('code', $value)->whereNot('id', $this->job_level)->exists()) {
                        $fail('Code already exist');
                    }
                }
            ],
        ];
    }
}
