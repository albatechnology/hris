<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Role\RoleResource;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $data = parent::toArray($request);
        $data['roles'] = RoleResource::collection($this->whenLoaded('roles'));
        $data['image'] = $this->image;

        if ($request->getRequestUri() === '/api/users/me') {
            return [
                ...$data,
                'permissions' => PermissionService::getMyPermissions(),
            ];
        }

        return [
            ...$data,
            // 'detail' => new UserDetailResource($this->whenLoaded('detail'))
        ];
    }
}
