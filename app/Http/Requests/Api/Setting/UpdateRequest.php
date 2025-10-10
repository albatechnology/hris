<?php

namespace App\Http\Requests\Api\Setting;

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Traits\Requests\RequestToBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use RequestToBoolean;

    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $setting = Setting::findTenanted($this->setting);
        return [
            'value' => $setting->key->getValidationRules(),
        ];
    }
}
