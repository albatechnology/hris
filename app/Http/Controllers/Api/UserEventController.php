<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserEvent\StoreRequest;
use App\Http\Resources\Event\EventResource;
use App\Models\Event;
use App\Models\User;
use Spatie\QueryBuilder\QueryBuilder;

class UserEventController extends BaseController
{
    public function index(int $id)
    {
        $event = Event::findTenanted($id);
        $data = QueryBuilder::for(User::tenanted()->whereHas('events', fn($q) => $q->where('Event_id', $event->id)))
            ->allowedSorts([
                'id',
                'event_id',
                'user_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return EventResource::collection($data);
    }

    public function store(int $id, StoreRequest $request)
    {
        $event = Event::findTenanted($id);
        try {
            if ($request->user_ids && count($request->user_ids) > 0) {
                $event->users()->syncWithoutDetaching($request->user_ids);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new EventResource($event);
    }

    public function destroy(int $id, int $userId)
    {
        $event = Event::findTenanted($id);
        $user = User::select('id')->tenanted()->where('id', $userId)->firstOrFail();
        try {
            $event->users()->detach($user->id);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
