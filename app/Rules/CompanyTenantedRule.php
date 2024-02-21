<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompanyTenantedRule implements ValidationRule
{
    public function __construct(public $model = null, public string $message = 'Company not found')
    {
        if (is_null($model)) {
            $this->model = \App\Models\Company::class;
        }
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $company = $this->model::tenanted()->firstWhere('id', $value);
        if (! $company) {
            $fail($this->message);
        }
    }
}
