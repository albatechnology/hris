<?php

namespace App\Http\Resources\UserContact;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return parent::toArray($request);
    }
}
