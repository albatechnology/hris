<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Event\CalendarRequest;
use App\Http\Requests\Api\Event\StoreRequest;
use App\Http\Resources\Event\EventResource;
use App\Models\Event;
use App\Models\Timeoff;
use App\Models\User;
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
        $event = QueryBuilder::for(Event::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::scope('where_year_month', 'whereYearMonth')
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'name',
                'type',
                'start_at',
                'end_at',
                'is_public',
                'is_send_email',
                'description',
                'created_at',
            ])
            ->paginate($this->per_page);

        return EventResource::collection($event);
    }

    public function show(int $id)
    {
        $event = Event::findTenanted($id);
        return new EventResource($event);
    }

    public function store(StoreRequest $request)
    {
        $event = Event::create($request->validated());

        return new EventResource($event);
    }

    public function update(int $id, StoreRequest $request)
    {
        $event = Event::findTenanted($id);
        $event->update($request->validated());

        return (new EventResource($event))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $event = Event::findTenanted($id);
        $event->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $event = Event::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $event->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $event = Event::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $event->restore();

        return new EventResource($event);
    }

    public function calendar(CalendarRequest $request)
    {
        // $fullDate = null; // now $fullDate not used, since filter per date handled by local frontend
        // if ($request->filter['date']) {
        //     $fullDate = sprintf('%s-%s-%s', $request->filter['year'], $request->filter['month'], $request->filter['date']);
        // }

        $companyId = $request->company_id ?? auth()->user()->company_id;

        $events = Event::tenanted()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            // ->orWhere(fn($q) => $q->whereNationalHoliday())
            // ->when(
            //     $fullDate,
            //     fn($q) => $q->whereDate('start_at', '<=', $fullDate)->whereDate('end_at', '>=', $fullDate),
            //     fn($q) => $q->whereYear('start_at', $request->filter['year'])->where(fn($q) => $q->whereMonth('start_at', $request->filter['month'])->orWhereMonth('end_at', $request->filter['month']))
            // )
            ->whereYear('start_at', $request->filter['year'])
            ->where(fn($q) => $q->whereMonth('start_at', $request->filter['month'])->orWhereMonth('end_at', $request->filter['month']))
            ->orderBy('start_at')
            ->get(['id', 'type', 'name', 'start_at', 'end_at', 'description']);

        $data = [];
        foreach ($events as $event) {
            // if ($fullDate || (date('Y-m-d', strtotime($event->start_at)) == date('Y-m-d', strtotime($event->end_at)))) {
            //     $data[] = [
            //         'type' => $event->type,
            //         'name' => $event->name,
            //         'date' => $fullDate ?? date('Y-m-d', strtotime($event->start_at)),
            //         'description' => $event->description,
            //     ];
            // } else {
            $startDate = $event->start_at;
            // if (date('m') != date('m', strtotime($startDate))) {
            //     $startDate = date('Y-m-01');
            // }

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
            // }
        }

        $birthdays = User::tenanted()
            ->whereHas('detail', fn($q) => $q->whereMonth('birthdate', $request->filter['month']))
            ->with('detail', fn($q) => $q->select('user_id', 'birthdate'))
            ->get(['id', 'name']);

        foreach ($birthdays as $user) {
            $data[] = [
                'type' => 'birthday',
                'name' => $user->name . " birthday",
                'date' => sprintf(date('Y') . '-%s-%s', date('m', strtotime($user->detail->birthdate)), date('d', strtotime($user->detail->birthdate))),
                'description' => $user->name . " birthday",
            ];
        }

        $timeoffs = Timeoff::tenanted()->approved()
            ->whereYear('start_at', $request->filter['year'])
            ->where(fn($q) => $q->whereMonth('start_at', $request->filter['month'])->orWhereMonth('end_at', $request->filter['month']))
            ->with([
                'timeoffPolicy' => fn($q) => $q->select('id', 'name'),
                'user' => fn($q) => $q->select('id', 'name'),
            ])
            ->orderBy('start_at')
            ->get(['user_id', 'timeoff_policy_id', 'start_at', 'end_at', 'reason']);

        foreach ($timeoffs as $timeoff) {
            // if ($fullDate || (date('Y-m-d', strtotime($timeoff->start_at)) == date('Y-m-d', strtotime($timeoff->end_at)))) {
            //     $data[] = [
            //         'type' => 'timeoff',
            //         'name' => $timeoff->user->name . ' ' . $timeoff->timeoffPolicy->name,
            //         'date' => $fullDate ?? date('Y-m-d', strtotime($timeoff->start_at)),
            //         'description' => $timeoff->reason,
            //     ];
            // } else {
            $startDate = $timeoff->start_at;
            // if (date('m') != date('m', strtotime($startDate))) {
            //     $startDate = date('Y-m-01');
            // }

            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($startDate)));
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($timeoff->end_at)));
            $dateRange = \Carbon\CarbonPeriod::create($startDate, $endDate);

            foreach ($dateRange as $date) {
                $data[] = [
                    'type' => 'timeoff',
                    'name' => $timeoff->user->name . ' ' . $timeoff->timeoffPolicy->name,
                    'date' => $date->format('Y-m-d'),
                    'description' => $timeoff->reason,
                ];
            }
            // }
        }

        return \App\Http\Resources\DefaultResource::collection($data);
    }
}
