<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

     /**
     * Prepare the data for validation.
     */
    // protected function prepareForValidation() : void
    // {
    //     $phone = new PhoneNumber($this->phone, 'ID');
    //     $this->merge([
    //         'phone' => $phone->formatForMobileDialingInCountry('ID'),
    //     ]);
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3',
            'type' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'phone' => 'required|numeric|digits_between:10,12|unique:users',
            // 'phone' => 'required|numeric|digits_between:10,12|unique:users|phone:ID',
            'password' => ['required', 'string', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            'coin' => 'nullable|integer|gt:0',
            'ticket' => 'nullable|integer|gt:0',
            'referral_code' => 'nullable|string|unique:users',
            'referred_by_user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
