<?php

namespace App\Http\Requests\Api\LockAttendance;

use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => 'nullable|array',
            'filter.search' => 'nullable|string',
        ];
    }
}
