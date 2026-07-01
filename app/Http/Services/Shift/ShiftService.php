<?php

namespace App\Http\Services\Shift;

use App\Exports\UserShiftsReport;
use App\Http\Services\BaseService;
use App\Imports\ShiftUsersImport;
use App\Interfaces\Repositories\Shift\ShiftRepositoryInterface;
use App\Interfaces\Services\Shift\ShiftServiceInterface;
use App\Models\Event;
use App\Models\Shift;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class ShiftService extends BaseService implements ShiftServiceInterface
{
    public function __construct(protected ShiftRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Shift
    {
        $data['show_in_request_branch_ids'] = $data['branch_ids'] ?? [];
        $data['show_in_request_department_ids'] = $data['department_ids'] ?? [];
        $data['show_in_request_position_ids'] = $data['position_ids'] ?? [];

        unset($data['branch_ids'], $data['department_ids'], $data['position_ids']);

        return $this->repository->create($data);
    }

    public function update(string $id, array $data): bool
    {
        $data['show_in_request_branch_ids'] = $data['branch_ids'] ?? [];
        $data['show_in_request_department_ids'] = $data['department_ids'] ?? [];
        $data['show_in_request_position_ids'] = $data['position_ids'] ?? [];

        unset($data['branch_ids'], $data['department_ids'], $data['position_ids']);

        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        $shift = $this->repository->findById($id, fn($q) => $q->withCount('schedules'));

        if ($shift->schedules_count > 0) {
            throw new \Exception('Shift is being used in the schedule. Remove it from the schedule first');
        }

        return $this->repository->delete($id);
    }

    public function forceDelete(string $id): bool
    {
        $shift = $this->repository->findById($id, fn($q) => $q->withTrashed()->withCount('schedules'));

        if ($shift->schedules_count > 0) {
            throw new \Exception('Shift is being used in the schedule. Remove it from the schedule first');
        }

        return $this->repository->forceDelete($id);
    }

    public function reportShiftUsers(array $filters, ?string $export = null)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $filters['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $filters['end_date']);
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        $isShowResignUsers = $filters['is_show_resign_users'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        $userIds = isset($filters['user_ids']) && !empty($filters['user_ids']) ? explode(',', $filters['user_ids']) : null;

        $users = User::tenanted(true)
            ->where('join_date', '<=', $startDate)
            ->where(fn($q) => $q->whereNull('resign_date')->orWhere('resign_date', '>=', $endDate))
            ->when($userIds, fn($q) => $q->whereIn('id', $userIds))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($isShowResignUsers, fn($q) => $q->showResignUsers($isShowResignUsers))
            ->get(['id', 'company_id', 'name', 'nik']);

        $data = [];
        $companyId = null;
        foreach ($users as $user) {
            if ($companyId !== $user->company_id) {
                $nationalHolidays = Event::whereCompany($user->company_id)->whereNationalHoliday()->get(['name', 'start_at', 'end_at']);
                $companyId = $user->company_id;
            }

            $user->setAppends([]);
            $dataShifts = [];
            foreach ($dateRange as $date) {
                $schedule = ScheduleService::getTodaySchedule($user, $date, ['id', 'name', 'is_overide_national_holiday', 'is_overide_company_holiday'], ['id', 'name']);

                $shift = $schedule?->shift?->name ?? null;

                if ($schedule?->is_overide_national_holiday == false) {
                    $nationalHoliday = $nationalHolidays->first(function ($nh) use ($date) {
                        return date('Y-m-d', strtotime($nh->start_at)) <= $date->format("Y-m-d") && date('Y-m-d', strtotime($nh->end_at)) >= $date->format("Y-m-d");
                    });

                    if ($nationalHoliday) {
                        $shift = 'national_holiday';
                    }
                }

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

        $result = [
            'dates' => $dateRange,
            'users' => $data,
        ];

        if ($export) {
            return Excel::download(new UserShiftsReport($result), 'user_shifts.xlsx');
        }

        return $result;
    }

    public function importShiftUsers($file)
    {
        Excel::import(new ShiftUsersImport(auth()->user()), $file);
    }
}