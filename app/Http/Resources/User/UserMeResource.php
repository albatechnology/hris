<?php

namespace App\Http\Resources\User;

use App\Http\Resources\DefaultResource;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $data = parent::toArray($request);
        $data['roles'] = DefaultResource::collection($this->whenLoaded('roles'));
        $data['image'] = $this->image;

        return [
            ...$data,
            'permissions' => PermissionService::getMyPermissions(),
        ];
    }
}
