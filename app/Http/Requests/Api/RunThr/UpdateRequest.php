<?php

namespace App\Http\Requests\Api\RunThr;

use App\Enums\RunPayrollStatus;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RunPayrollStatus::class)],
            // 'payment_date' => 'nullable|date',
        ];
    }
}
