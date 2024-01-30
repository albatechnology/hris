<?php

namespace App\Http\Resources\Schedule;

use App\Http\Resources\Shift\ShiftResource;
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
            'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
        ];
    }
}
