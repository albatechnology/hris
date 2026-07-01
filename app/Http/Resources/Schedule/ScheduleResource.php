<?php

namespace App\Http\Resources\Schedule;

use App\Http\Resources\DefaultResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'shifts' => DefaultResource::collection($this->whenLoaded('shifts')),
        ];
    }
}
