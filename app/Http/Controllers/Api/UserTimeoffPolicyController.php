<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserTimeoffPolicy\StoreRequest;
use App\Http\Resources\TimeoffPolicy\TimeoffPolicyResource;
use App\Models\TimeoffPolicy;
use App\Models\User;

class UserTimeoffPolicyController extends BaseController
{
    public function store(int $timeoffPolicyId, StoreRequest $request)
    {
        $timeoffPolicy = TimeoffPolicy::select('id')->tenanted()->where('id', $timeoffPolicyId)->firstOrFail();
        try {
            if ($request->user_ids && count($request->user_ids) > 0) {
                $timeoffPolicy->users()->syncWithoutDetaching($request->user_ids);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new TimeoffPolicyResource($timeoffPolicy);
    }

    public function destroy(int $timeoffPolicyId, User $user)
    {
        $timeoffPolicy = TimeoffPolicy::select('id')->tenanted()->where('id', $timeoffPolicyId)->firstOrFail();
        try {
            $timeoffPolicy->users()->detach($user->id);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
