<?php

namespace App\Http\Requests\Api\RunReprimand;

use App\Enums\RunReprimandStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['required', Rule::enum(RunReprimandStatus::class)],
        ];

        // return [
        //     // 'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
        //     'type' => ['required', Rule::enum(ReprimandType::class)],
        //     'effective_date' => 'required|date',
        //     'end_date' => ['required', 'date', function ($attribute, $value, $fail) {
        //         if (date('Y-m-d', strtotime($value)) < date('Y-m-d', strtotime($this->effective_date))) {
        //             $fail("End date must be greater than Effective date");
        //         }
        //     }],
        //     'notes' => 'nullable|string',
        //     'watcher_ids.*' => ['required', new CompanyTenantedRule(User::class, 'Watcher not found')],
        //     'file' => 'nullable|mimes:' . config('app.file_mimes_types'),
        // ];
    }
}
