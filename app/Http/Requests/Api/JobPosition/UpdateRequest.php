<?php

namespace App\Http\Requests\Api\JobPosition;

use App\Models\JobPosition;
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
            'parent_id' => 'nullable|exists:job_positions,id',
            'name' => 'required|string',
            'code' => [
                'required',
                'string',
                function (mixed $attribute, string $value, Closure $fail) {
                    if (JobPosition::tenanted()->where('code', $value)->whereNot('id', $this->job_position)->exists()) {
                        $fail('Code already exist');
                    }
                }
            ],
        ];
    }
}
