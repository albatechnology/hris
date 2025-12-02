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
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:250'],
            'start_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'description' => ['required', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['required', 'image', config('app.image_mimes_types'), 'max:5120']
        ];
    }
}
