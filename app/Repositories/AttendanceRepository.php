<?php
namespace App\Repositories;

use App\Enums\AttendanceType;
use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Attendance\AttendanceRepositoryInterface;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function getApprovals(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AttendanceDetail::where('type',AttendanceType::MANUAL)
                ->myApprovals()
                ->with([
                    'attendance' => fn($q) => $q->with([
                    'user' => fn($q) => $q->select('id', 'name'),
                    'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                    'schedule' => fn($q) => $q->select('id', 'name'),
                ]),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
            ]);
            return QueryBuilder::for($query)
                ->allowedFilters([
                    AllowedFilter::scope('approval_status','whereApprovalStatus'),
                    AllowedFilter::scope('branch_id','whereBranch'),
                    AllowedFilter::scope('name','whereUserName'),
                    'created_at'
                ])
                ->allowedSorts([
                    'id',
                    'created_at'
                ])
                ->paginate($perPage);
    }

    public function getApprovalDetail(AttendanceDetail $attendanceDetail): AttendanceDetail
    {
       return $attendanceDetail->load(
            [
                // 'attendance' => fn($q) => $q->select('id', 'user_id', 'shift_id', 'schedule_id')
                'attendance' => fn($q) => $q
                    ->with([
                        'user' => fn($q) => $q->select('id', 'name'),
                        'shift' => fn($q) => $q->withTrashed()->selectMinimalist(),
                        'schedule' => fn($q) => $q->select('id', 'name')
                    ])
            ]
        );
    }

    public function findTodayAttendance(string $date, ?int $scheduleId, ?int $shiftId, User $user, bool $checkByDetails = true):?Attendance
    {
        return Attendance::where('user_id',$user->id)
        ->whereDate('date',date('Y-m-d',strtotime($date)))
        ->when($scheduleId, fn($q)=>$q->where('schedule_id',$scheduleId))
        ->when($shiftId, fn($q)=>$q->where('shift_id',$shiftId))
        ->when($checkByDetails, fn($q)=> $q->whereHas('details'))
        ->first();
    }

    public function createAttendance(array $data):Attendance
    {
        return Attendance::create($data);
    }

    public function createAttendanceDetail(Attendance $attendance, array $data):AttendanceDetail
    {
        return $attendance->details()->create($data);
    }

    public function summaryEmployees(array $filters, ?string $sort = null, int $perPage = 15)
    {
        $query = Attendance::query()
            ->with(['user:id,name','shift:id,name','schedule:id,name'])
            ->when($filters['branch_id'] ?? null, fn($q,$branchId)=>
                $q->whereHas('user',fn($q2)=>$q2->where('branch_id',$branchId))
            )
            ->when($filters['date'] ?? null, fn($q,$date)=>
                $q->whereDate('date',$date)
        );
        if($sort){
            $query->orderBy($sort);
        }
        return $query->paginate($perPage);
    }

    public function getUsersForSummary(?int $branchId, ?array $userIds, ?bool $isShowResignUsers, ?string $search)
    {
        return User::select('id','branch_id','name','nik')
            ->tenanted(true)
            ->when($branchId, fn($q)=> $q->where('branch_id',$branchId))
            ->when($userIds, fn($q)=>$q->whereIn('id',$userIds))
            ->when($isShowResignUsers, fn($q)=> $q->showResignUsers($isShowResignUsers))
            ->when($search, function($q, $search){
                $q->where(function($sub) use ($search){
                    $sub->where('name','LIKE',"%$search%")
                    ->orWhere('nik','LIKE',"%$search%");
                });
            })
            ->with([
                'branch'=>fn($q) => $q->select('id','name'),
                'payrollinfo'=> fn($q)=> $q->select('user_id','is_ignore_alpa')
            ])
            ->get();
    }

    public function getCompanyHolidays(int $companyId, string $date)
    {
        return Event::selectMinimalist()
            ->whereCompany($companyId)
            ->whereDateBetween($date,$date)
            ->whereCompanyHoliday()
            ->get();
    }

    public function getNationalHolidays(int $companyId, string $date)
    {
        return Event::selectMinimalist()
            ->whereCompany($companyId)
            ->whereDateBetween($date,$date)
            ->whereNationalHoliday()
            ->get();
    }
}
