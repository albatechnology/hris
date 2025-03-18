<?php

namespace App\Http\Requests\Api\Loan;

use App\Enums\LoanType;
use App\Models\Loan;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'effective_date' => 'required|date',
            'type' => ['required', Rule::enum(LoanType::class)],
            'installment' => 'required|integer',
            'interest' => 'nullable|numeric',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'details' => 'required|array',
            'details.*.payment_period_year' => 'required|date_format:Y',
            'details.*.payment_period_month' => 'required|date_format:m',
            'details.*.basic_payment' => 'required|numeric',
            'details.*.interest' => 'nullable|numeric',
        ];
    }
}
