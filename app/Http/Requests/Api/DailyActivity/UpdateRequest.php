<?php

namespace App\Http\Requests\Api\DailyActivity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['required','string'],
            'images.*' => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'] // max 2MB per image
        ];
    }
}
