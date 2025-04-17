<?php

namespace App\Http\Resources\Setting;

use App\Enums\SettingValueType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $valueData = $this->value;

        if ($this->value_type->is(SettingValueType::MODEL)) {
            $valueData = User::select('id', 'name')->firstWhere('id', $this->value);
        }

        return [
            ...parent::toArray($request),
            'value_data' => $valueData
        ];
    }
}
