<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Event\CalendarRequest;
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
        $event = QueryBuilder::for(Event::tenanted()->orWhere(fn ($q) => $q->whereNationalHoliday()))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'name', 'type', 'start_at', 'end_at', 'is_public', 'is_send_email', 'description', 'created_at',
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

    public function update(int $id, StoreRequest $request)
    {
        $event = Event::tenanted()->where('id', $id)->firstOrFail();
        $event->update($request->validated());

        return (new EventResource($event))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(string $id)
    {
        $event = Event::tenanted()->where('id', $id)->firstOrFail(['id']);
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

    public function calendar(CalendarRequest $request)
    {
        $fullDate = null;
        if ($request->filter['date']) {
            $fullDate = sprintf('%s-%s-%s', $request->filter['year'], $request->filter['month'], $request->filter['date']);
        }

        $events = Event::tenanted()->orWhere(fn ($q) => $q->whereNationalHoliday())
            ->when($fullDate, fn ($q) => $q->whereDate('start_at', '<=', $fullDate)->whereDate('end_at', '>=', $fullDate), fn ($q) => $q->whereYear('start_at', $request->filter['year'])->where(fn ($q) => $q->whereMonth('start_at', $request->filter['month'])->orWhereMonth('end_at', $request->filter['month'])))
            ->get(['id', 'type', 'name', 'start_at', 'end_at', 'description']);

        $data = [];
        foreach ($events as $event) {
            if ($fullDate) {
                $data[] = [
                    'type' => $event->type,
                    'name' => $event->name,
                    'date' => $fullDate,
                    'description' => $event->description,
                ];
            } elseif (date('Y-m-d', strtotime($event->start_at)) == date('Y-m-d', strtotime($event->end_at))) {
                $data[] = [
                    'type' => $event->type,
                    'name' => $event->name,
                    'date' => date('Y-m-d', strtotime($event->start_at)),
                    'description' => $event->description,
                ];
            } else {
                $startDate = $event->start_at;
                if (date('m') != date('m', strtotime($startDate))) {
                    $startDate = date('Y-m-01');
                }

                $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($startDate)));
                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($event->end_at)));
                $dateRange = \Carbon\CarbonPeriod::create($startDate, $endDate);

                foreach ($dateRange as $date) {
                    $data[] = [
                        'type' => $event->type,
                        'name' => $event->name,
                        'date' => $date->format('Y-m-d'),
                        'description' => $event->description,
                    ];
                }
            }
        }

        return \App\Http\Resources\DefaultResource::collection($data);
    }
}
