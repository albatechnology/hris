<?php

namespace App\Http\Requests\Api\Level;

use Illuminate\Foundation\Http\FormRequest;

class LevelUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        
        return true;
    }

    public function rules(): array
    {
        // dd($this->all());
        return [
            'name' => 'required|string',
        ];
    }
}
