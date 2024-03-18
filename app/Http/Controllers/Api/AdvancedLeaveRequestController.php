<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Requests\Api\AdvancedLeaveRequest\StoreRequest;
use App\Http\Requests\Api\AdvancedLeaveRequest\ApproveRequest;
use App\Http\Resources\AdvancedLeaveRequest\AdvancedLeaveRequestResource;
use App\Models\AdvancedLeaveRequest;
use App\Models\TimeoffRegulation;
use App\Models\TimeoffRegulationMonth;
use App\Services\AdvancedLeaveRequestService;
use App\Services\TimeoffRegulationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AdvancedLeaveRequestController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        // $this->middleware('permission:advanced_leave_request_access', ['only' => ['restore']]);
        // $this->middleware('permission:advanced_leave_request_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:advanced_leave_request_create', ['only' => 'store']);
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(AdvancedLeaveRequest::query())
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
            ])
            ->paginate($this->per_page);

        return AdvancedLeaveRequestResource::collection($data);
    }

    public function show(AdvancedLeaveRequest $advancedLeaveRequest): AdvancedLeaveRequestResource
    {
        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function store(StoreRequest $request): AdvancedLeaveRequestResource|JsonResponse
    {
        try {
            $advancedLeaveRequest = AdvancedLeaveRequest::create($request->validated());

            $notificationType = NotificationType::REQUEST_ADVANCED_LEAVE;
            $advancedLeaveRequest->user->manager?->notify(new ($notificationType->getNotificationClass())($notificationType, $advancedLeaveRequest->user));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function approve(ApproveRequest $request, AdvancedLeaveRequest $advancedLeaveRequest): AdvancedLeaveRequestResource|JsonResponse
    {
        // dd($request->validated());

        /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();
        $timeoffRegulation = TimeoffRegulation::tenanted()->where('company_id', $user->company_id)->first();

        $totalDayAdvanceLeaveReequest = $advancedLeaveRequest->amount;
        $startMonth = date('m');
        $endMonth = date('m', strtotime('+ ' . $timeoffRegulation->max_advanced_leave_request . 'month'));

        if ($user->timeoffRegulationMonths->count() > 0) {
        } else {
            $timeoffRegulationMonths = $timeoffRegulation->timeoffPeriodRegulations()->orderByDesc('min_working_month')->get()->first(fn ($timeoffPeriodRegulation) => TimeoffRegulationService::isJoinDatePassed($user->join_date, $timeoffPeriodRegulation->min_working_month))?->timeoffRegulationMonths;

            // $timeoffRegulationMonths->map(function ($timeoffRegulationMonth) use ($totalDayAdvanceLeaveRequest, $startMonth, $endMonth) {
            // });

            foreach ($timeoffRegulationMonths as $timeoffRegulationMonth) {
                if (
                    $timeoffRegulationMonth->month > $startMonth &&
                    $timeoffRegulationMonth->month <= $endMonth &&
                    $totalDayAdvanceLeaveReequest > 0
                ) {
                    // dump($timeoffRegulationMonth);

                    $amount = max($timeoffRegulationMonth->amount - $totalDayAdvanceLeaveReequest, 0);
                    $totalDayAdvanceLeaveReequest -= $timeoffRegulationMonth->amount;
                    $timeoffRegulationMonth->amount = $amount;
                    // dump($totalDayAdvanceLeaveReequest);
                    // dd($timeoffRegulationMonth);
                }
            }
            dd($timeoffRegulationMonths);
        }
        try {
            $advancedLeaveRequest->update($request->validated());

            $notificationType = NotificationType::ADVANCED_LEAVE_APPROVED;
            $advancedLeaveRequest->user->notify(new ($notificationType->getNotificationClass())($notificationType, $advancedLeaveRequest->approvedBy, $advancedLeaveRequest->is_approved));
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new AdvancedLeaveRequestResource($advancedLeaveRequest);
    }

    public function approvals()
    {
        $query = AdvancedLeaveRequest::whereHas('user', fn ($q) => $q->where('manager_id', auth('sanctum')->id()));

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
            ])
            ->allowedSorts([
                'id', 'date',
            ])
            ->paginate($this->per_page);

        return AdvancedLeaveRequestResource::collection($data);
    }

    public function getAvailableDays()
    {
        $availableDays = AdvancedLeaveRequestService::getAvailableDays();

        return response()->json(['data' => ['available_days' => $availableDays]]);
    }
}
