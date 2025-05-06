<?php

namespace App\Http\Requests\Api\AbsenceReminder;

use App\Models\Client;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // client_id is for SMART
        $clientId = $this->client_id ?? null;
        $companyId = $this->company_id ?? null;
        if ($clientId) {
            $companyId = Client::tenanted()->where('id', $clientId)->firstOrFail(['company_id'])->company_id;
        }

        $this->merge([
            'client_id' => $clientId,
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
            'client_id' => Rule::requiredIf(config('app.name') === "SMART"),
            'company_id' => ['required', new CompanyTenantedRule()],
            'minutes_before' => 'required|numeric',
            'minutes_repeat' => 'required|numeric',
        ];
    }
}
