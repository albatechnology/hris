<?php

use App\Http\Controllers\Api\AdvancedLeaveRequestController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientLocationController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GuestBookController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\IncidentTypeController;
use App\Http\Controllers\Api\LiveAttendanceController;
use App\Http\Controllers\Api\LiveAttendanceLocationController;
use App\Http\Controllers\Api\NationalHolidayController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NppController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\OvertimeRequestController;
use App\Http\Controllers\Api\PanicController;
use App\Http\Controllers\Api\PatrolController;
use App\Http\Controllers\Api\PatrolLocationController;
use App\Http\Controllers\Api\PatrolTaskController;
use App\Http\Controllers\Api\PayrollComponentController;
use App\Http\Controllers\Api\PayrollProrateController;
use App\Http\Controllers\Api\PayrollScheduleController;
use App\Http\Controllers\Api\PayrollSettingController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RequestChangeDataAllowesController;
use App\Http\Controllers\Api\RequestChangeDataController;
use App\Http\Controllers\Api\RequestScheduleController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RunPayrollController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SupervisorTypeController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskHourController;
use App\Http\Controllers\Api\TaskRequestController;
use App\Http\Controllers\Api\TimeoffController;
use App\Http\Controllers\Api\TimeoffPeriodRegulationController;
use App\Http\Controllers\Api\TimeoffPolicyController;
use App\Http\Controllers\Api\TimeoffRegulationController;
use App\Http\Controllers\Api\TimeoffRegulationMonthController;
use App\Http\Controllers\Api\UpdatePayrollComponentController;
use App\Http\Controllers\Api\UserContactController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserCustomFieldController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserEventController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserPatrolController;
use App\Http\Controllers\Api\UserPatrolTaskController;
use App\Http\Controllers\Api\UserScheduleController;
use App\Http\Controllers\Api\UserTimeoffPolicyController;
use App\Http\Controllers\Api\UserPayrollInfoController;
use App\Http\Controllers\Api\UserTransferController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('token', 'login');
});

Route::post('notifications/test', [NotificationController::class, 'test']);

Route::post('auth/setup-password/resend', [AuthController::class, 'resendSetupPassword']);
Route::post('auth/setup-password', [AuthController::class, 'setupPassword']);

Route::get('users/backupPhoto', [UserController::class, 'backupPhoto']);
Route::group(['middleware' => ['auth:sanctum', 'verified']], function () {
    Route::post('users/import', [UserController::class, 'import']);
    Route::get('users/me', [UserController::class, 'me']);
    Route::post('users/register', [UserController::class, 'register']);
    Route::post('users/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::post('users/update-device', [UserController::class, 'updateDevice']);
    Route::get('users/tasks', [UserController::class, 'tasks']);
    Route::post('users/fcm-token', [UserController::class, 'fcmToken']);
    Route::put('users/password', [UserController::class, 'updatePassword']);
    Route::group(['prefix' => 'users/{user}'], function () {
        Route::get('payroll', [UserController::class, 'payroll']);
        Route::get('companies', [UserController::class, 'companies']);
        Route::get('branches', [UserController::class, 'branches']);
        Route::post('detail', [UserController::class, 'detail']);
        Route::apiResource('experiences', UserExperienceController::class);
        Route::apiResource('educations', UserEducationController::class);
        Route::apiResource('contacts', UserContactController::class);
        Route::apiResource('custom-fields', UserCustomFieldController::class)->except('destroy');
        Route::put('salary', [UserPayrollInfoController::class, 'salary']);
        Route::put('bank-information', [UserPayrollInfoController::class, 'bankInformation']);
        Route::put('tax-configuration', [UserPayrollInfoController::class, 'taxConfiguration']);
        Route::put('bpjs-configuration', [UserPayrollInfoController::class, 'bpjsConfiguration']);
        Route::put('payroll-component', [UserPayrollInfoController::class, 'payrollComponent']);
        Route::post('request-change-data', [UserController::class, 'requestChangeData']);
        Route::get('get-available-supervisors', [UserController::class, 'getAvailableSupervisor']);
        Route::post('set-supervisors', [UserController::class, 'setSupervisors']);
    });

    Route::apiResource('users', UserController::class);

    Route::apiResource('roles', RoleController::class);
    Route::get('permissions/all', [\App\Http\Controllers\Api\PermissionController::class, 'all']);

    Route::apiResource('groups', GroupController::class);

    Route::group(['prefix' => 'companies/{company}'], function () {
        // Route::group(['prefix' => 'timeoff-regulations/{timeoff_regulation}'], function () {
        //     Route::apiResource('periods/{period}/months', TimeoffRegulationMonthController::class);
        //     Route::apiResource('periods', TimeoffPeriodRegulationController::class);
        // });

        Route::apiResource('timeoff-regulation/periods/{period}/months', TimeoffRegulationMonthController::class)->except('store', 'destroy');
        Route::apiResource('timeoff-regulation/periods', TimeoffPeriodRegulationController::class);
        Route::get('timeoff-regulation', [TimeoffRegulationController::class, 'index']);
        Route::post('timeoff-regulation', [TimeoffRegulationController::class, 'store']);
        Route::put('timeoff-regulation', [TimeoffRegulationController::class, 'update']);
        Route::get('request-change-data-allowances', [RequestChangeDataAllowesController::class, 'index']);
        Route::post('request-change-data-allowances', [RequestChangeDataAllowesController::class, 'store']);
    });
    Route::apiResource('companies', CompanyController::class)->except('destroy');

    Route::apiResource('branches', BranchController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('departments', DepartmentController::class);

    Route::apiResource('announcements', AnnouncementController::class)->only(['index', 'show', 'store']);

    Route::apiResource('shifts', ShiftController::class);

    Route::get('schedules/today', [ScheduleController::class, 'today']);
    Route::group(['prefix' => 'schedules/{schedule}'], function () {
        // Route::put('shifts', [ScheduleController::class, 'updateShifts']);
        Route::get('download-template-import', [ScheduleController::class, 'downloadTemplateImport']);
        Route::post('import-shifts', [ScheduleController::class, 'importShifts']);
        Route::get('users', [UserScheduleController::class, 'index']);
        Route::post('users', [UserScheduleController::class, 'store']);
        Route::put('restore', [ScheduleController::class, 'restore']);
        Route::delete('force-delete', [ScheduleController::class, 'forceDelete']);
        Route::delete('users/{user}', [UserScheduleController::class, 'destroy']);
    });
    Route::apiResource('schedules', ScheduleController::class);

    Route::get('attendances/employees/summary', [AttendanceController::class, 'employeesSummary']);
    Route::get('attendances/employees', [AttendanceController::class, 'employees']);
    Route::get('attendances/logs', [AttendanceController::class, 'logs']);
    Route::get('attendances/report/{export?}', [AttendanceController::class, 'report']);
    Route::post('attendances/manual-attendances', [AttendanceController::class, 'manualAttendance']);
    Route::post('attendances/request', [AttendanceController::class, 'request']);
    Route::get('attendances/approvals', [AttendanceController::class, 'approvals']);
    Route::get('attendances/approvals/count-total', [AttendanceController::class, 'countTotalapprovals']);
    Route::get('attendances/approvals/{attendance_detail}', [AttendanceController::class, 'showApproval']);
    Route::put('attendances/approvals/{attendance_detail}', [AttendanceController::class, 'approve']);
    Route::apiResource('attendances', AttendanceController::class);

    Route::group(['prefix' => 'timeoff-policies/{timeoff_policy}'], function () {
        Route::post('users', [UserTimeoffPolicyController::class, 'store']);
        Route::delete('users/{user}', [UserTimeoffPolicyController::class, 'destroy']);
    });
    Route::apiResource('timeoff-policies', TimeoffPolicyController::class);

    Route::get('timeoffs/approvals', [TimeoffController::class, 'approvals']);
    Route::get('timeoffs/approvals/count-total', [TimeoffController::class, 'countTotalapprovals']);
    Route::group(['prefix' => 'timeoffs/{timeoff}'], function () {
        Route::put('approve', [TimeoffController::class, 'approve']);
    });
    Route::apiResource('timeoffs', TimeoffController::class);

    Route::apiResource('overtimes', OvertimeController::class);
    Route::post('overtimes/user-settings', [OvertimeController::class, 'userSetting']);

    Route::get('overtime-requests/approvals', [OvertimeRequestController::class, 'approvals']);
    Route::get('overtime-requests/approvals/count-total', [OvertimeRequestController::class, 'countTotalApprovals']);
    Route::apiResource('overtime-requests', OvertimeRequestController::class)->except('update');
    Route::put('overtime-requests/{overtime_request}/approve', [OvertimeRequestController::class, 'approve']);

    Route::get('live-attendances/users', [LiveAttendanceController::class, 'users']);
    // Route::get('live-attendances/locations', [LiveAttendanceController::class, 'locations']);
    Route::group(['prefix' => 'live-attendances/{live_attendance}'], function () {
        Route::apiResource('locations', LiveAttendanceLocationController::class);
    });
    Route::apiResource('live-attendances', LiveAttendanceController::class);

    Route::apiResource('supervisor-types', SupervisorTypeController::class);
    Route::apiResource('national-holidays', NationalHolidayController::class);

    Route::group(['prefix' => 'events/{event}'], function () {
        Route::post('users', [UserEventController::class, 'store']);
        Route::delete('users/{user}', [UserEventController::class, 'destroy']);
    });
    Route::get('calendar', [EventController::class, 'calendar']);
    Route::apiResource('events', EventController::class);
    Route::apiResource('custom-fields', CustomFieldController::class);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/count-total', [NotificationController::class, 'countTotal']);
    Route::put('notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    Route::get('advanced-leave-requests/get-available-days', [AdvancedLeaveRequestController::class, 'getAvailableDays']);
    Route::get('advanced-leave-requests/approvals', [AdvancedLeaveRequestController::class, 'approvals']);
    Route::get('advanced-leave-requests/approvals/count-total', [AdvancedLeaveRequestController::class, 'countTotalApprovals']);
    Route::put('advanced-leave-requests/{advanced_leave_request}/approve', [AdvancedLeaveRequestController::class, 'approve']);
    Route::apiResource('advanced-leave-requests', AdvancedLeaveRequestController::class);

    Route::get('formulas/components/{formula_component}', [\App\Http\Controllers\Api\FormulaController::class, 'components']);
    Route::get('formulas/amounts', [\App\Http\Controllers\Api\FormulaController::class, 'amounts']);

    Route::apiResource('payroll-components', PayrollComponentController::class);
    Route::get('payroll-setting', [PayrollSettingController::class, 'index']);
    Route::put('payroll-setting', [PayrollSettingController::class, 'update']);

    Route::get('payroll-schedule', [PayrollScheduleController::class, 'index']);
    Route::put('payroll-schedule', [PayrollScheduleController::class, 'update']);

    Route::get('payroll-prorate', [PayrollProrateController::class, 'index']);
    Route::put('payroll-prorate', [PayrollProrateController::class, 'update']);

    Route::apiResource('update-payroll-components', UpdatePayrollComponentController::class);

    Route::put('run-payrolls/user-components/{run_payroll_user}', [RunPayrollController::class, 'updateUserComponent']);
    Route::get('run-payrolls/{run_payroll}/export', [RunPayrollController::class, 'export']);
    Route::apiResource('run-payrolls', RunPayrollController::class);

    Route::get('request-change-datas/approvals', [RequestChangeDataController::class, 'approvals']);
    Route::get('request-change-datas/approvals/count-total', [RequestChangeDataController::class, 'countTotalApprovals']);
    Route::put('request-change-datas/{request_change_data}/approve', [RequestChangeDataController::class, 'approve']);
    Route::apiResource('request-change-datas', RequestChangeDataController::class)->only(['index', 'show']);

    Route::get('request-schedules/approvals', [RequestScheduleController::class, 'approvals']);
    Route::get('request-schedules/approvals/count-total', [RequestScheduleController::class, 'countTotalApprovals']);
    Route::put('request-schedules/{request_schedule}/approve', [RequestScheduleController::class, 'approve']);
    Route::apiResource('request-schedules', RequestScheduleController::class);

    Route::get('task-requests/approvals', [TaskRequestController::class, 'approvals']);
    Route::get('task-requests/approvals/count-total', [TaskRequestController::class, 'countTotalApprovals']);
    Route::put('task-requests/{task_request}/approve', [TaskRequestController::class, 'approve']);
    Route::apiResource('task-requests', TaskRequestController::class);
    Route::group(['prefix' => 'task-hours/{task_hour}'], function () {
        Route::get('users', [TaskHourController::class, 'users']);
        Route::post('users', [TaskHourController::class, 'addUsers']);
        Route::delete('users', [TaskHourController::class, 'deleteUsers']);
    });
    Route::apiResource('task-hours', TaskHourController::class);
    Route::group(['prefix' => 'tasks/{task}'], function () {
        Route::put('restore', [TaskController::class, 'restore']);
        Route::delete('force-delete', [TaskController::class, 'forceDelete']);
    });
    Route::apiResource('tasks', TaskController::class);

    Route::get('user-transfers/approvals', [UserTransferController::class, 'approvals']);
    Route::get('user-transfers/approvals/count-total', [UserTransferController::class, 'countTotalapprovals']);
    Route::group(['prefix' => 'user-transfers/{user_transfer}'], function () {
        Route::put('approve', [UserTransferController::class, 'approve']);
    });
    Route::apiResource('user-transfers', UserTransferController::class);

    Route::apiResource('panics', PanicController::class);
    Route::get('panics/users/my-panic', [PanicController::class, 'myPanic']);

    Route::apiResource('incident-types', IncidentTypeController::class);
    Route::apiResource('incidents', IncidentController::class);

    Route::get('clients/summaries', [ClientController::class, 'summary']);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('client-locations', ClientLocationController::class);
    Route::apiResource('guest-books', GuestBookController::class);

    Route::apiResource('user-patrol-tasks', UserPatrolTaskController::class);
    Route::group(['prefix' => 'patrols/{patrol}'], function () {
        Route::get('export', [PatrolController::class, 'export']);
        Route::get('users', [PatrolController::class, 'userIndex']);
        Route::get('users/{user_patrol_id}', [PatrolController::class, 'userShow']);
        Route::post('users', [PatrolController::class, 'userStore']);
        Route::put('users/{user_patrol_id}', [PatrolController::class, 'userUpdate']);
        Route::delete('users/{user_patrol_id}', [PatrolController::class, 'userDestroy']);

        Route::group(['prefix' => 'locations/{location}'], function () {
            Route::apiResource('tasks', PatrolTaskController::class);
        });
        Route::apiResource('locations', PatrolLocationController::class);
        Route::post('locations/attend/manual', [PatrolLocationController::class, 'attend']);
        Route::post('locations/attend/scan-qr-code', [PatrolLocationController::class, 'scanQrCode']);
    });
    Route::apiResource('patrols', PatrolController::class);

    Route::apiResource('user-patrols', UserPatrolController::class);

    Route::apiResource('npp', NppController::class);

    Route::apiResource('settings', SettingController::class);
});
