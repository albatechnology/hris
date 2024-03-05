<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\Timeoff\ApproveRequest;
use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Http\Resources\Timeoff\TimeoffResource;
use App\Models\Attendance;
use App\Models\Timeoff;
use App\Models\UserTimeoffHistory;
use App\Services\ScheduleService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:timeoff_access', ['only' => ['index', 'show', 'restore']]);
        // $this->middleware('permission:timeoff_access', ['only' => ['restore']]);
        // $this->middleware('permission:timeoff_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:timeoff_create', ['only' => 'store']);
        // $this->middleware('permission:timeoff_edit', ['only' => 'update']);
        // $this->middleware('permission:timeoff_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Timeoff::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('timeoff_policy_id'),
                AllowedFilter::exact('delegate_to'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                'request_type',
            ])
            ->allowedIncludes(['user', 'timeoffPolicy', 'delegateTo'])
            ->allowedSorts([
                'id', 'user_id', 'timeoff_policy_id', 'delegate_to', 'start_at', 'end_at', 'request_type', 'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }

    public function show(Timeoff $timeoff)
    {
        $timeoff->load(['user', 'timeoffPolicy', 'delegateTo']);

        return new TimeoffResource($timeoff);
    }

    public function store(StoreRequest $request)
    {
        if (!ScheduleService::checkAvailableSchedule(startDate: $request->start_at, endDate: $request->end_at)) {
            return $this->errorResponse(message: 'Schedule is not available', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $timeoff = Timeoff::create($request->validated());

            $notificationType = NotificationType::NEED_TIMEOFF_APPROVAL;
            $timeoff->user->manager?->notify(new ($notificationType->getNotificationClass())($notificationType, $timeoff->user));
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage());
        }

        return new TimeoffResource($timeoff);
    }

    public function update(Timeoff $timeoff, StoreRequest $request)
    {
        if (!ScheduleService::checkAvailableSchedule(startDate: $request->start_at, endDate: $request->end_at)) {
            return $this->errorResponse(message: 'Schedule is not available', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $timeoff->update($request->validated());

        return (new TimeoffResource($timeoff))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Timeoff $timeoff)
    {
        $timeoff->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $timeoff = Timeoff::withTrashed()->findOrFail($id);
        $timeoff->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $timeoff = Timeoff::withTrashed()->findOrFail($id);
        $timeoff->restore();

        return new TimeoffResource($timeoff);
    }

    public function approve(Timeoff $timeoff, ApproveRequest $request)
    {
        // dump($request->validated());
        // dump($timeoff);
        $timeoff->is_approved = $request->is_approved;
        if (!$timeoff->isDirty('is_approved')) {
            return $this->errorResponse('Nothing to update', [], Response::HTTP_BAD_REQUEST);
        }

        $startSchedule = ScheduleService::getTodaySchedule($timeoff->user, $timeoff->start_at);
        $endSchedule = ScheduleService::getTodaySchedule($timeoff->user, $timeoff->end_at);
        if (!$startSchedule && !$endSchedule) {
            return $this->errorResponse(message: sprintf('There is a schedule that cannot be found between %s and %s', $timeoff->start_at, $timeoff->end_at), code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dateRange = new \DatePeriod(date_create($timeoff->start_at), new \DateInterval('P1D'), date_create($timeoff->end_at));

        // untuk history timeoff
        $value = 0.5;
        if ($timeoff->request_type->is(TimeoffRequestType::FULL_DAY)) {
            $startDate = new \DateTime($timeoff->start_at);
            $endDate = new \DateTime($timeoff->end_at);
            if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
                $value = 1;
            } else {
                $interval = $startDate->diff($endDate);
                $value = $interval->days;
            }
        }

        DB::beginTransaction();
        try {
            $timeoff->approved_by = auth('sanctum')->user()->id;
            $timeoff->approved_at = now();

            // 1. kalo pending, is_approved=null dan delete attendance
            // 2. kalo approve, is_approved=1 dan create attendance
            // 3. kalo reject, is_approved=0 dan delete attendance

            if (is_null($timeoff->is_approved) || $timeoff->is_approved === false) {
                Attendance::where('timeoff_id', $timeoff->id)->delete();

                UserTimeoffHistory::create([
                    'user_id' => $timeoff->user_id,
                    'is_increment' => true,
                    'value' => $value,
                    'properties' => ['user' => $timeoff->user, 'timeoff' => $timeoff, 'timeoff_policy' => $timeoff->timeoffPolicy],
                    'description' => sprintf(UserTimeoffHistory::DESCRIPTION['TIMEOFF'], $timeoff->timeoffPolicy->name),
                ]);
            } else {
                foreach ($dateRange as $date) {
                    $schedule = ScheduleService::getTodaySchedule($timeoff->user, $date->format('Y-m-d'));
                    Attendance::create([
                        'user_id' => $timeoff->user_id,
                        'schedule_id' => $schedule->id,
                        'shift_id' => $schedule->shift->id,
                        'timeoff_id' => $timeoff->id,
                        'code' => $timeoff->timeoffPolicy->code,
                        'date' => $date->format('Y-m-d'),
                    ]);
                }

                UserTimeoffHistory::create([
                    'user_id' => $timeoff->user_id,
                    'is_increment' => false,
                    'value' => $value,
                    'properties' => ['user' => $timeoff->user, 'timeoff' => $timeoff, 'timeoff_policy' => $timeoff->timeoffPolicy],
                    'description' => sprintf(UserTimeoffHistory::DESCRIPTION['TIMEOFF'], $timeoff->timeoffPolicy->name),
                ]);
            }

            $timeoff->save();

            if (!is_null($timeoff->is_approved)) {
                $notificationType = NotificationType::TIMEOFF_APPROVED;
                $timeoff->user?->notify(new ($notificationType->getNotificationClass())($notificationType, $timeoff->user->manager, $timeoff->is_approved));
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new TimeoffResource($timeoff);
    }
}
