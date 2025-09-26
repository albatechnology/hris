<?php

namespace App\Http\Requests\Api\Level;

use Illuminate\Foundation\Http\FormRequest;

class LevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Bisa diganti dengan policy/authorization sesuai kebutuhan
        return true;
    }

    public function rules(): array
    {
        // dd($this->all());
        return [
            'company_id' => 'required',
            'name' => 'required|string',
        ];
    }
}
