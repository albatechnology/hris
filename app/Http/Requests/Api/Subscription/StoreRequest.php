<?php

namespace App\Http\Requests\Api\Subscription;

use Illuminate\Foundation\Http\FormRequest;

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
            'active_end_date' => $this->active_end_date ?? date('Y-m-d', strtotime('+' . config('app.free_trial_max_weeks') . ' days')),
            'max_companies' => $this->max_companies ?? config('app.free_trial_min_data'),
            'max_users' => $this->max_users ?? config('app.free_trial_max_data'),
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
            'name' => 'required|string',
            'email' => 'required|unique:users,email',
            'phone' => 'required|string|max:16',
            'company_name' => 'required|string|max:50',
            'company_address' => 'required|string|max:150',
            'active_end_date' => 'required|date',
            'max_companies' => 'required|integer',
            'max_users' => 'required|integer',
            'price' => 'nullable|integer',
            'discount' => 'nullable|integer',
            'total_price' => 'nullable|integer',
        ];
    }
}
