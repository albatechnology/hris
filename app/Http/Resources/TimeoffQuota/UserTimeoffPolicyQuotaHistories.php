<?php

namespace App\Http\Resources\TimeoffQuota;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserTimeoffPolicyQuotaHistories extends JsonResource
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
            'balance' => $this->balance,
        ];
    }
}
