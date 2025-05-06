<?php

namespace App\Http\Requests\Api\PayrollSetting;

use App\Models\Client;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
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
        // client_id is for Syntegra
        $clientId = $this->filter['client_id'] ?? null;
        $companyId = $this->filter['company_id'] ?? null;
        if ($clientId) {
            $companyId = Client::tenanted()->where('id', $clientId)->first(['company_id'])->company_id;
        }

        $this->merge([
            'filter' => [
                'client_id' => $clientId,
                'company_id' => $companyId,
            ]
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 'filter.company_id' => 'required',
        return [
            'filter.client_id' => Rule::requiredIf(config('app.name') === "Syntegra"),
            'filter.company_id' => ['required', new CompanyTenantedRule()],
        ];
    }
}
