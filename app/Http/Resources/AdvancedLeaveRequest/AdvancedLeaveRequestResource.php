<?php

namespace App\Http\Resources\AdvancedLeaveRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvancedLeaveRequestResource extends JsonResource
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
