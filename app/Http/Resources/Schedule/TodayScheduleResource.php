<?php

namespace App\Http\Resources\Schedule;

use App\Http\Resources\LiveAttendance\LiveAttendanceResource;
use App\Http\Resources\Shift\ShiftResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodayScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        /** @var \App\Models\LiveAttendance $liveAttendance */
        $liveAttendance = auth('sanctum')->user()?->liveAttendance()->first(['id', 'name', 'is_flexible'])?->load('locations');

        return [
            ...parent::toArray($request),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'live_attendance' => new LiveAttendanceResource($liveAttendance),
        ];
    }
}
