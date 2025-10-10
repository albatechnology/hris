<?php

namespace App\Http\Resources\Attendance;

use App\Models\AttendanceDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceApprovalsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $data = parent::toArray($request);
        $histories = AttendanceDetail::select(['is_clock_in', 'time', 'created_at'])
            ->where('attendance_id', $data['attendance_id'])
            ->whereDate('time', date('Y-m-d', strtotime($data['time'])))
            ->approved()
            ->orderByDesc('is_clock_in')
            ->orderBy('time')
            ->get();

        return [
            ...$data,
            'histories' => $histories
        ];
    }
}
