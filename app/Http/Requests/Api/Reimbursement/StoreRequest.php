<?php

namespace App\Http\Requests\Api\Reimbursement;

use App\Enums\ReimbursementPeriodType;
use App\Models\ReimbursementCategory;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Closure;
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
        $this->merge([
            'user_id' => $this->user_id ?? auth()->id(),
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
            'user_id' => ['required', 'exists:users,id'],
            'reimbursement_category_id' => function ($attribute, $value, Closure $fail) {
                $reimbursementCategory = ReimbursementCategory::select('id', 'company_id')->tenanted()->where('id', $value)->first();
                if (!$reimbursementCategory) {
                    return $fail('Reimbursement Category not found');
                }

                $isUserHasReimbursementCategory = User::select('id')->where('id', $this->user_id)->whereHas('reimbursementCategories', fn($q) => $q->where('id', $reimbursementCategory->id))->exists();
                if (!$isUserHasReimbursementCategory) {
                    return $fail('User does not have this Reimbursement Category');
                }
            },
            'date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1', 'max:4000000000'],
            'description' => ['required', 'string'],

            'files' => 'nullable|array',
            'files.*' => 'required|mimes:' . config('app.file_mimes_types'),
        ];
    }
}
