<?php

namespace App\Interfaces\Repositories\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Interfaces\Repositories\BaseRepositoryInterface;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface {
    public function getApprovals(array $filters, int $perPage = 15):LengthAwarePaginator;    
    public function getApprovalDetail(AttendanceDetail $attendanceDetail):AttendanceDetail;
    public function findTodayAttendance(string $date, ?int $scheduleId, ?int $shiftId, User $user, bool $checkByDetails = true):?Attendance;
    public function createAttendance(array $data):Attendance;
    public function createAttendanceDetail(Attendance $attendance, array $data):AttendanceDetail;
    public function summaryEmployees(array $filters, ?string $sort = null, int $perPage = 15);
    public function getUsersForSummary(?int $branchId, ?array $userIds, ?bool $isShowResignUsers, ?string $search);
    public function getCompanyHolidays(int $companyId, string $date);
    public function getNationalHolidays(int $companyId, string $date);
}
