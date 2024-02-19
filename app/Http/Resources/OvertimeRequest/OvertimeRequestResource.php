<?php

namespace App\Http\Resources\OvertimeRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeRequestResource extends JsonResource
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
            'user' => $this->user,
            'shift' => $this->shift,
            'overtime' => $this->overtime,
            'status_updated_by' => $this->statusUpdatedBy ?? $this->status_updated_by,
        ];
    }
}
