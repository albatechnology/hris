<?php

namespace App\Http\Resources\User;

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
        // $data['type'] = $this->type->description;
        // $data['roles'] = RoleResource::collection($this->whenLoaded('roles'));

        // if ($request->getRequestUri() === '/api/users/me') {
        //     return [
        //         ...$data,
        //         'permissions' => PermissionsHelper::getMyPermissions(),
        //     ];
        // }

        return [
            ...$data,
            // 'detail' => new UserDetailResource($this->whenLoaded('detail'))
        ];
    }
}
