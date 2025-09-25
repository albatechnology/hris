<?php

namespace App\Http\Services\Attendance;

use App\Enums\MediaCollection;
use App\Models\User;
use DomainException;
use App\Models\Attendance;
use App\Models\LockAttendance;
use App\Models\AttendanceDetail;
use App\Services\Aws\Rekognition;
use App\Services\ScheduleService;
use App\Http\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Interfaces\Services\Attendance\AttendanceServiceInterface;
use App\Interfaces\Repositories\Attendance\AttendanceRepositoryInterface;

class AttendanceService extends BaseService implements AttendanceServiceInterface
{
    protected AttendanceRepositoryInterface $attendanceRepository;

    public function __construct(AttendanceRepositoryInterface $attendanceRepository)
    {
        parent::__construct($attendanceRepository);
        $this->attendanceRepository = $attendanceRepository;
    }

//    public function getApprovals(array $filters, int $perPage = 15): LengthAwarePaginator
//    {
//         return $this->attendanceRepository->getApprovals($filters,$perPage);
//    }

//  public function __construct(AttendanceRepositoryInterface $attendanceRepository)
//     {
//         parent::__construct($attendanceRepository);
//     }

    public static function inLockAttendance(string $date, ?User $user = null): bool
    {
        if (config('app.name') != 'SUNSHINE') return false;

        if (!$user) {
            /** @var User $user */
            $user = auth('sanctum')->user();
        }

        return LockAttendance::whereCompany($user->company_id)
            ->whereDateIn($date)
            ->exists();
    }

    public function getApprovals(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // /** @var AttendanceRepositoryInterface $repo */
        // $repo = $this->baseRepository;
        return $this->attendanceRepository->getApprovals($filters, $perPage);
    }

    public function getApprovalDetail(AttendanceDetail $attendanceDetail): AttendanceDetail
    {
        // /** @var AttendanceRepositoryInterface $repo */
        // $repo = $this->baseRepository;
        return $this->attendanceRepository->getApprovalDetail($attendanceDetail);
    }

    public function storeAttendance(array $data, User $user):Attendance
    {
        if(self::inLockAttendance($data['time'],$user)){
            throw new \DomainException('Attendance is locked.');
        }

        $attendance = null;
        if($data['is_offline_mode']){
            $attendance = $this->attendanceRepository->findTodayAttendance($data['time'],null, null,$user,false);

            if($attendance){
                $data['schedule_id'] = $attendance->schedule_id;
                $data['shift_id'] = $attendance->shift_id;
            }else{
                $schedule = ScheduleService::getTodaySchedule($user, $data['time']);
                $data['schedule_id'] = $schedule->id;
                $data['shift_id'] = $schedule->shift->id;
            }
        }else{
            $attendance = $this->attendanceRepository->findTodayAttendance($data['time'],$data['schedule_id'],$data['shift_id'],$user,false);
        }

        if(config('app.enable_face_rekognition') === true && !$data['is_offline_mode']){
            $compareFace = Rekognition::compareFace($user, $data['file']);
            if(!$compareFace){
                throw new DomainException('Face not match!');
            }
        }
        return DB::transaction(function () use($attendance,$data,$user) {
            if(!$attendance){
                $attendance = $this->attendanceRepository->createAttendance([
                    'user_id'=>$user->id,
                    'date'=>date('Y-m-d',strtotime($data['time'])),
                    'schedule_id'=>$data['schedule_id'],
                    'shift_id'=>$data['shift_id'],
                ]);
            }

            $attendanceDetail = $this->attendanceRepository->createAttendanceDetail($attendance,$data);

            if(isset($data['file']) && $data['file']->isValid()){
                $mediaCollection = MediaCollection::ATTENDANCE->value;
                $attendanceDetail
                ->addMedia($data['file'])
                ->toMediaCollection($mediaCollection);
            }
            return $attendance;
        });
    }

    public function employeesSummary(array $filters, ?string $sort = null): array
    {

        return [];
    }

    private function calculateEmployeeSummary($users, string $date,string $scheduleType, $companyHolidays, $nationalHolidays):array
    {
        $summaryPresentOnTime = 0;
        $summaryPresentLateClockIn = 0;
        $summaryPresentEarlyClockOut = 0;
        $summaryNotPresentAbsent = 0;
        $summaryNotPresentClockIn =0;
        $summaryNotPresentClockOut = 0;
        $summaryAwayTimeOff = 0;    
        
        foreach ($users as $user) {
            $schedule = ScheduleService::getTodaySchedule($user,$date, scheduleType:$scheduleType);

            $attendance = $user->attendances()
                ->where('date',$date)
                ->with([
                    'shift'
                ]);
        }
        return [];
    }
    
}
