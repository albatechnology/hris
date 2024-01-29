<?php

namespace App\Http\Requests\Api\User;

use App\Enums\PtkpStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollInfoStoreRequest extends FormRequest
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
            'bpjs_ketenagakerjaan_no' => 'nullable|string',
            'bpjs_kesehatan_no' => 'nullable|string',
            'npwp' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account_no' => 'nullable|string',
            'bank_account_holder' => 'nullable|string',
            'ptkp_status' => ['nullable', Rule::enum(PtkpStatus::class)],
        ];
    }
}
