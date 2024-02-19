<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Event\StoreRequest;
use App\Http\Resources\Event\EventResource;
use App\Models\Event;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EventController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:event_access', ['only' => ['restore']]);
        $this->middleware('permission:event_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:event_create', ['only' => 'store']);
        $this->middleware('permission:event_edit', ['only' => 'update']);
        $this->middleware('permission:event_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $event = QueryBuilder::for(Event::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'name', 'type', 'start_at', 'end_at', 'is_public', 'is_send_email', 'description', 'created_at'
            ])
            ->paginate($this->per_page);

        return EventResource::collection($event);
    }

    public function show(Event $event)
    {
        return new EventResource($event);
    }

    public function store(StoreRequest $request)
    {
        $event = Event::create($request->validated());

        return new EventResource($event);
    }

    public function update(Event $event, StoreRequest $request)
    {
        $event->update($request->validated());

        return (new EventResource($event))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $event = Event::withTrashed()->findOrFail($id);
        $event->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $event = Event::withTrashed()->findOrFail($id);
        $event->restore();
        return new EventResource($event);
    }
}
