<?php

namespace App\Http\Requests\Api\ExtraOff;

use Illuminate\Foundation\Http\FormRequest;

class EligibleUsersRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'nullable|date',
            'days' => 'nullable|integer',
        ];
    }
}
