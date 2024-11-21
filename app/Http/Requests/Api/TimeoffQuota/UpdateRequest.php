<?php

namespace App\Http\Requests\Api\TimeoffQuota;

use App\Models\TimeoffQuota;
use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $timeoffQuota = TimeoffQuota::select('id', 'used_quota')->findTenanted($this->timeoff_quota);
        return [
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'required|date',
            'quota' => [
                'required',
                'numeric',
                'min:' . $timeoffQuota->used_quota,
                'multiple_of:0.5'
            ],
            'description' => 'required|string',
        ];
    }
}
