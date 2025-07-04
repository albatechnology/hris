<?php

namespace App\Http\Requests\Api\RunPayroll;

use App\Models\Bank;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    const TYPE = ['new', 'active', 'resign', 'new_and_resign'];
    use RequestToBoolean;

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'type' => $this->type && in_array($this->type, self::TYPE) ? $this->type : null,
            'is_only_active_users' => $this->toBoolean($this->is_only_active_users),
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
            'type' => ['nullable', 'string', Rule::in(self::TYPE)],
            'bank_id' => ['required', new CompanyTenantedRule(Bank::class, 'Bank not found')],
        ];
    }
}
