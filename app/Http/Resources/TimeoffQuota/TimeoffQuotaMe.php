<?php

namespace App\Http\Resources\TimeoffQuota;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeoffQuotaMe extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'remaining_balance' => $this->remaining_balance,
            'timeoff_policy' => $this->timeoffPolicy,
        ];
    }
}
