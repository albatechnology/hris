<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Shift\StoreRequest;
use App\Http\Resources\Shift\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Exports\UserShiftsReport;
use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Http\Resources\DefaultResource;
use App\Imports\ShiftUsersImport;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;

class ShiftController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:shift_access', ['only' => ['restore']]);
        $this->middleware('permission:shift_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:shift_create', ['only' => 'store']);
        $this->middleware('permission:shift_edit', ['only' => 'update']);
        $this->middleware('permission:shift_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Shift::tenanted()->orWhereNull('company_id'))
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
                'type',
                'clock_in',
                'clock_out',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'clock_in',
                'clock_out',
                'created_at',
            ])
            ->paginate($this->per_page);

        return ShiftResource::collection($data);
    }

    public function show(int $id)
    {
        $shift = Shift::findTenanted($id);
        return new ShiftResource($shift);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['show_in_request_branch_ids'] = $request->branch_ids;
        $data['show_in_request_department_ids'] = $request->department_ids;
        $data['show_in_request_position_ids'] = $request->position_ids;
        $shift = Shift::create($data);

        return new ShiftResource($shift);
    }

    public function update(int $id, StoreRequest $request)
    {
        $shift = Shift::findTenanted($id);

        $data = $request->validated();
        $data['show_in_request_branch_ids'] = $request->branch_ids;
        $data['show_in_request_department_ids'] = $request->department_ids;
        $data['show_in_request_position_ids'] = $request->position_ids;
        $shift->update($data);

        return (new ShiftResource($shift))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $shift = Shift::withCount('schedules')->findTenanted($id);

        if ($shift->schedules_count > 0) {
            return $this->errorResponse(message: 'Shift is being used in the schedule. Remove it from the schedule first', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $shift->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $shift = Shift::withTrashed()->tenanted()->where('id', $id)->fisrtOrFail();

        if ($shift->schedules_count > 0) {
            return $this->errorResponse(message: 'Shift is being used in the schedule. Remove it from the schedule first', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $shift->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $shift = Shift::withTrashed()->tenanted()->where('id', $id)->fisrtOrFail();
        $shift->restore();

        return new ShiftResource($shift);
    }

    public function reportShiftUsers(ExportReportRequest $request, ?string $export = null)
    {
        $userIds = $request->filter['user_ids'] ?? null;

        $startDate = Carbon::createFromFormat('Y-m-d', $request->filter['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->filter['end_date']);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $users = User::tenanted(true)
            ->where('join_date', '<=', $startDate)
            ->where(fn($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
            ->when($userIds, fn($q) => $q->whereIn('id', explode(',', $userIds)))
            ->get(['id', 'company_id', 'name', 'last_name', 'nik']);

        $data = [];
        foreach ($users as $user) {
            // $companyHolidays = Event::whereCompany($user->company_id)->whereCompanyHoliday()->get(['start_at', 'end_at']);
            // $nationalHolidays = Event::whereCompany($user->company_id)->whereNationalHoliday()->get(['start_at', 'end_at']);

            $user->setAppends([]);
            $dataShifts = [];
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'name']);

                $shift = $schedule?->shift?->name ?? null;

                // if ($schedule?->is_overide_company_holiday == false) {
                //     $companyHoliday = $companyHolidays->first(function ($ch) use ($date) {
                //         return date('Y-m-d', strtotime($ch->start_at)) <= $date->format("Y-m-d") && date('Y-m-d', strtotime($ch->end_at)) >= $date->format("Y-m-d");
                //     });

                //     if ($companyHoliday) {
                //         $shift = 'company_holiday';
                //         // $shiftType = 'company_holiday';
                //     }
                // }

                // if ($schedule?->is_overide_national_holiday == false && is_null($companyHoliday)) {
                //     $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                //         return date('Y-m-d', strtotime($nh->start_at)) <= $date->format("Y-m-d") && date('Y-m-d', strtotime($nh->end_at)) >= $date->format("Y-m-d");
                //     });

                //     if ($nationalHoliday) {
                //         $shift = 'national_holiday';
                //         // $shiftType = 'national_holiday';
                //     }
                // }

                $dataShifts[] = [
                    'date' => $date->format("Y-m-d"),
                    'shift' => $shift,
                ];
            }

            $data[] = [
                'user' => $user,
                'shifts' => $dataShifts,
            ];
        }

        $data = [
            'dates' => $dateRange,
            'users' => $data,
        ];

        if ($export) return Excel::download(new UserShiftsReport($data), 'user_shifts.xlsx');

        return DefaultResource::collection($data);
    }

    public function importShiftUsers(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        Excel::import(new ShiftUsersImport(auth()->user()), $request->file('file'));

        return $this->updatedResponse();
    }
}
