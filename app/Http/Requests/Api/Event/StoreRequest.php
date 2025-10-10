<?php

namespace App\Http\Requests\Api\Event;

use App\Enums\EventType;
use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $companyId = $this->company_id ?? null;
        if ($this->branch_id) {
            $companyId = Branch::tenanted()->where('id', $this->branch_id)->firstOrFail(['company_id'])->company_id;
        }

        $this->merge([
            'company_id' => $companyId
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
            'company_id' => ['required', new CompanyTenantedRule()],
            'branch_id' => [Rule::requiredIf(config('app.name') === "Syntegra"), new CompanyTenantedRule(Branch::class)],
            'name' => 'required|string',
            'type' => ['required', Rule::enum(EventType::class)],
            'start_at' => 'required|date_format:Y-m-d',
            'end_at' => 'required|date_format:Y-m-d',
            'is_public' => 'nullable|boolean',
            'is_send_email' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];
    }
}
