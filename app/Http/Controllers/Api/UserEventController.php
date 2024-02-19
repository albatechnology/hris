<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserEvent\StoreRequest;
use App\Http\Resources\Event\EventResource;
use App\Models\Event;
use App\Models\User;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserEventController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:schedule_access', ['only' => ['index', 'show', 'restore']]);
        // $this->middleware('permission:schedule_access', ['only' => ['restore']]);
        // $this->middleware('permission:schedule_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:schedule_create', ['only' => 'store']);
        // $this->middleware('permission:schedule_edit', ['only' => 'update']);
        // $this->middleware('permission:schedule_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(Event $event)
    {
        $data = QueryBuilder::for(User::tenanted()->whereHas('Events', fn ($q) => $q->where('Event_id', $event->id)))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
            ])
            ->allowedSorts([
                'id', 'event_id', 'user_id', 'created_at'
            ])
            ->paginate($this->per_page);

        return EventResource::collection($data);
    }

    public function store(Event $event, StoreRequest $request)
    {
        try {
            if ($request->user_ids && count($request->user_ids) > 0) {
                $event->users()->syncWithoutDetaching($request->user_ids);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new EventResource($event);
    }

    public function destroy(Event $event, User $user)
    {
        try {
            $event->users()->detach($user->id);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
