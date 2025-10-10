<?php

namespace App\Interfaces\Services\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Interfaces\Services\BaseServiceInterface;

interface AttendanceServiceInterface extends BaseServiceInterface
{
    public function getApprovals(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function getApprovalDetail(AttendanceDetail $attendanceDetail):AttendanceDetail;
    public function storeAttendance(array $data, User $user):Attendance;
    // public function summaryEmployees(array $filters, ?string $sort = null, int $perPage = 15);
    public function employeesSummary(array $filters, ?string $sort = null): array;
    
}
