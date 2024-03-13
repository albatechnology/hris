<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'details' => AttendanceDetailResource::collection($this->whenLoaded('details')),
            // 'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
        ];
    }
}
