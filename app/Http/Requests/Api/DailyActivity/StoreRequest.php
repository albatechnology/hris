<?php

namespace App\Http\Requests\Api\DailyActivity;

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
            'user_id' => auth()->id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required','exists:users,id'],
            'description' => ['required','string'],
            'images.*' => ['nullable','image','mimes:jpeg,png,jpg,gif','max:5120'] // max 2MB per image
        ];
    }
}
