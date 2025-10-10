<?php

namespace App\Http\Requests\Api\Subscription;

use Illuminate\Foundation\Http\FormRequest;

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
            'active_end_date' => 'required|date',
            'max_companies' => 'required|integer',
            'max_users' => 'required|integer',
            'price' => 'nullable|integer',
            'discount' => 'nullable|integer',
            // 'total_price' => 'nullable|integer',
        ];
    }
}
