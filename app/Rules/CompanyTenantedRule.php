<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompanyTenantedRule implements ValidationRule
{
    public function __construct(
        private $model = null,
        private string $message = 'Company not found',
        private ?Closure $query = null
    ) {
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
        $data = $this->model::tenanted()
            ->when($this->query, $this->query)
            ->firstWhere('id', $value);

        if (!$data) {
            $fail($this->message);
        }
    }
}
