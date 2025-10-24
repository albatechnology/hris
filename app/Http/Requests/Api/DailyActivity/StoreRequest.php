<?php

namespace App\Http\Requests\Api\DailyActivity;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required','exists:users,id'],
            'description' => ['required','string'],
            'images.*' => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'] // max 2MB per image
        ];
    }
}
