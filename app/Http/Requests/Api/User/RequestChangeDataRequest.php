<?php

namespace App\Http\Requests\Api\User;

use App\Enums\RequestChangeDataType;
use Illuminate\Foundation\Http\FormRequest;

class RequestChangeDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $data = $this->all() ?? [];

        $description = $data['description'] ?? null;
        $file = $data['file'] ?? null;

        if (isset($data['photo_profile']) && !is_null($data['photo_profile'])) {
            $data['photo_profile'] = $data['photo_profile'];
        }

        unset($data['description']);
        unset($data['file']);

        $this->replace([
            'description' => $description,
            'file' => $file,
            'details' => count($data) > 0 ? $data : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validations = [];
        foreach ($this->details as $type => $value) {
            $requestChangeDataType = RequestChangeDataType::tryFrom($type);
            if ($requestChangeDataType) {
                if ($requestChangeDataType->is(RequestChangeDataType::EMAIL)) {
                    $user = \App\Models\User::where('id', $this->segment(3))->firstOrFail(['id']);
                    $validations['details.' . $type] = $requestChangeDataType->getValidation($user);
                } else {
                    $validations['details.' . $type] = $requestChangeDataType->getValidation();
                }
            }
        }

        return [
            'description' => 'nullable|string',
            'file' => 'nullable|array',
            'file.*' => 'required|mimes:' . config('app.file_mimes_types'),
            'details' => 'required|array',
            ...$validations
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'details' => 'Data empty!',
        ];
    }
}
