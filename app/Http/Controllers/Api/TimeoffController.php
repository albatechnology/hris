<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\NotificationType;
use App\Enums\TimeoffRequestType;
use App\Http\Requests\Api\Timeoff\ApproveRequest;
use App\Http\Requests\Api\Timeoff\StoreRequest;
use App\Http\Resources\Timeoff\TimeoffResource;
use App\Models\Attendance;
use App\Models\Timeoff;
use App\Models\UserTimeoffHistory;
use App\Services\ScheduleService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TimeoffController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:timeoff_access', ['only' => ['restore']]);
        $this->middleware('permission:timeoff_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:timeoff_create', ['only' => 'store']);
        $this->middleware('permission:timeoff_edit', ['only' => ['update', 'approve']]);
        $this->middleware('permission:timeoff_delete', ['only' => ['destroy', 'forceDelete']]);
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
                'approval_status',
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
        $timeoff->load(['user', 'timeoffPolicy', 'approvedBy', 'delegateTo']);

        return new TimeoffResource($timeoff);
    }

    public function store(StoreRequest $request)
    {
        if (!ScheduleService::checkAvailableSchedule(startDate: $request->start_at, endDate: $request->end_at)) {
            return $this->errorResponse(message: 'Schedule is not available', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // if ($request->is_advanced_leave) {
        //     1, cek min_advanced_leave_working_month di timeoff_regulations
        //     2. cek history advanced leave user, apakah sudah melebihi max_advanced_leave_request
        //     3.
        // }
        // dd($request->validated());

        DB::beginTransaction();
        try {
            $timeoff = Timeoff::create($request->validated());

            $notificationType = NotificationType::REQUEST_TIMEOFF;
            $timeoff->user->approval?->notify(new ($notificationType->getNotificationClass())($notificationType, $timeoff->user, $timeoff));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
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
        $timeoff->approval_status = $request->approval_status;
        // dd($timeoff);
        if (!$timeoff->isDirty('approval_status')) {
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
            $timeoff->approved_by = auth('sanctum')->id();
            $timeoff->approved_at = now();

            // 1. kalo pending, approval_status=null dan delete attendance
            // 2. kalo approve, approval_status=1 dan create attendance
            // 3. kalo reject, approval_status=0 dan delete attendance
            if ($timeoff->approval_status->in([ApprovalStatus::PENDING, ApprovalStatus::REJECTED])) {
                Attendance::where('timeoff_id', $timeoff->id)->delete();

                UserTimeoffHistory::create([
                    'user_id' => $timeoff->user_id,
                    'is_increment' => true,
                    'value' => $value,
                    'properties' => ['user' => $timeoff->user, 'timeoff' => $timeoff, 'timeoff_policy' => $timeoff->timeoffPolicy],
                    'description' => sprintf(UserTimeoffHistory::DESCRIPTION['TIMEOFF'], $timeoff->timeoffPolicy->name),
                ]);
            } else {
                if ($value > $timeoff->user->total_timeoff) {
                    return response()->json(['message' => 'Leave request exceeds leave quota.'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

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

            if (!$timeoff->approval_status->is(ApprovalStatus::PENDING)) {
                $notificationType = NotificationType::TIMEOFF_APPROVED;
                $timeoff->user?->notify(new ($notificationType->getNotificationClass())($notificationType, $timeoff->user->approval, $timeoff->approval_status, $timeoff));
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new TimeoffResource($timeoff);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = DB::table('timeoffs')->where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = Timeoff::whereHas('user', fn ($q) => $q->where('approval_id', auth('sanctum')->id()))
            ->with('user', fn ($q) => $q->select('id', 'name'));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('timeoff_policy_id'),
                AllowedFilter::exact('delegate_to'),
                AllowedFilter::scope('start_at'),
                AllowedFilter::scope('end_at'),
                'request_type',
                'approval_status',
            ])
            ->allowedIncludes(['user', 'timeoffPolicy', 'approvedBy', 'delegateTo'])
            ->allowedSorts([
                'id', 'user_id', 'timeoff_policy_id', 'delegate_to', 'start_at', 'end_at', 'request_type', 'created_at',
            ])
            ->paginate($this->per_page);

        return TimeoffResource::collection($data);
    }
}
