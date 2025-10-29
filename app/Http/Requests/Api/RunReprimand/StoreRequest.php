<?php

namespace App\Http\Requests\Api\RunReprimand;

use App\Models\User;
use App\Rules\CompanyTenantedRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'user_ids' => [
                'nullable',
                'string',
                function (string $attr, string $value, Closure $fail) {
                    collect(explode(',', $value))->each(function ($id) use ($fail) {
                        $user = User::tenanted()->where('id', $id)->where('company_id', $this->company_id)->select('id')->exists();

                        if (!$user) {
                            $fail('The selected user ids is invalid (' . $id . ')');
                        }
                    });
                },
            ],
        ];
    }
}
