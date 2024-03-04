<?php

namespace App\Http\Resources\UserEducation;

use App\Enums\MediaCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserEducationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            ...parent::toArray($request),
            'file' => $this->getFirstMediaUrl(MediaCollection::USER_EDUCATION->value)
        ];
    }
}
