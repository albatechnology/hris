<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize() : bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules() : array
    {
        $user = $this->user;
        return [
            'name' => 'required|string|min:3',
            'type' => 'required|string',
            'email' => 'required|string|email|unique:users,email,' . $user->id,
            'phone' => 'required|numeric|digits_between:10,12|unique:users,phone,' . $user->id,
            'coin' => 'nullable|integer',
            'ticket' => 'nullable|integer',
            'referral_code' => 'nullable|string|unique:users,referral_code,' . $user->id,
            'referred_by_user_id' => 'nullable|integer|exists:users,id',
            // 'password' => 'nullable|string|min:8|confirmed',
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
        ];
    }
}
