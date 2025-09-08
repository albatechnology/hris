<?php

use App\Http\Controllers\Api\AbsenceReminderController;
use App\Http\Controllers\Api\AdvancedLeaveRequestController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchLocationController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ExtraOffController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GuestBookController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\IncidentTypeController;
use App\Http\Controllers\Api\LiveAttendanceController;
use App\Http\Controllers\Api\LiveAttendanceLocationController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LockAttendanceController;
use App\Http\Controllers\Api\MediaController;
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
use App\Http\Controllers\Api\ReimbursementCategoryController;
use App\Http\Controllers\Api\ReimbursementController;
use App\Http\Controllers\Api\ReprimandController;
use App\Http\Controllers\Api\RequestChangeDataAllowesController;
use App\Http\Controllers\Api\RequestChangeDataController;
use App\Http\Controllers\Api\RequestScheduleController;
use App\Http\Controllers\Api\RequestShiftController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RunPayrollController;
use App\Http\Controllers\Api\RunThrController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SupervisorRequestScheduleController;
use App\Http\Controllers\Api\SupervisorTypeController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskHourController;
use App\Http\Controllers\Api\TaskRequestController;
use App\Http\Controllers\Api\TimeoffController;
use App\Http\Controllers\Api\TimeoffPeriodRegulationController;
use App\Http\Controllers\Api\TimeoffPolicyController;
use App\Http\Controllers\Api\TimeoffQuotaController;
use App\Http\Controllers\Api\TimeoffQuotaHistoryController;
use App\Http\Controllers\Api\TimeoffRegulationController;
use App\Http\Controllers\Api\TimeoffRegulationMonthController;
use App\Http\Controllers\Api\UpdatePayrollComponentController;
use App\Http\Controllers\Api\UserContactController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserCustomFieldController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserEventController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserPatrolBatchController;
use App\Http\Controllers\Api\UserPatrolMovementController;
use App\Http\Controllers\Api\UserPatrolController;
use App\Http\Controllers\Api\UserPatrolTaskController;
use App\Http\Controllers\Api\UserScheduleController;
use App\Http\Controllers\Api\UserPayrollInfoController;
use App\Http\Controllers\Api\UserTransferController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

Route::post('atara/contact', [\App\Http\Controllers\Api\AtaraController::class, 'contact']);
Route::post('subscriptions', [SubscriptionController::class, 'store']);

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('token', 'login');
});

Route::post('notifications/test/{token}', [NotificationController::class, 'test']);

Route::post('auth/setup-password/resend', [AuthController::class, 'resendSetupPassword']);
Route::post('auth/setup-password', [AuthController::class, 'setupPassword']);

Route::group(['prefix' => 'auth/forgot-password/', 'controller' => ForgotPasswordController::class], function () {
    Route::post('send-otp', 'sendOtp');
    Route::post('verify-otp', 'verifyOtp');
    Route::post('update-password', 'updatePassword');
});

Route::get('users/backupPhoto', [UserController::class, 'backupPhoto']);
Route::group(['middleware' => ['auth:sanctum', 'verified']], function () {
    Route::post('import-timeoff-quotas', [TimeoffQuotaController::class, 'importTimeoffQuota']);
    Route::get('users/updateSupervisor', [UserController::class, 'updateSupervisor']);
    Route::get('users/export', [UserController::class, 'export']);
    Route::get('users/import/{sample?}', [UserController::class, 'import']);
    Route::post('users/import', [UserController::class, 'import']);
    Route::get('users/me', [UserController::class, 'me']);
    Route::post('users/register', [UserController::class, 'register']);
    Route::post('users/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::post('users/update-device', [UserController::class, 'updateDevice']);
    Route::get('users/tasks', [UserController::class, 'tasks']);
    Route::post('users/fcm-token', [UserController::class, 'fcmToken']);
    Route::put('users/password', [UserController::class, 'updatePassword']);
    Route::post('users/verify-password', [UserController::class, 'verifyPassword']);
    Route::group(['prefix' => 'users/{user}'], function () {
        Route::get('payroll', [UserController::class, 'payroll']);
        Route::get('thr', [UserController::class, 'thr']);
        Route::get('companies', [UserController::class, 'companies']);
        Route::get('branches', [UserController::class, 'branches']);

        Route::get('reimbursement-balance', [ReimbursementCategoryController::class, 'getUserBalance']);

        Route::get('timeoff-quotas', [TimeoffQuotaController::class, 'getUserTimeoffPolicyQuota']);
        Route::get('timeoff-quotas/{timeoff_policy}', [TimeoffQuotaController::class, 'getUserTimeoffPolicyQuotaHistories']);
        Route::put('detail', [UserController::class, 'detail']);
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
        Route::post('resign', [UserController::class, 'resign']);
        Route::post('cancel-resign', [UserController::class, 'cancelResign']);
        Route::post('rehire', [UserController::class, 'rehire']);
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

    Route::get('branches/summaries', [BranchController::class, 'summary']);
    Route::apiResource('branches', BranchController::class);
    Route::post('branch-locations/generate-qr-code', [BranchLocationController::class, 'generateQrCode']);
    Route::apiResource('branch-locations', BranchLocationController::class);

    Route::apiResource('positions', PositionController::class);
    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('departments', DepartmentController::class);

    Route::apiResource('announcements', AnnouncementController::class)->only(['index', 'show', 'store', 'destroy']);

    Route::get('shifts/report-shift-users/{export?}', [ShiftController::class, 'reportShiftUsers']);
    Route::post('shifts/import-shift-users', [ShiftController::class, 'importShiftUsers']);
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

    Route::group(['prefix' => 'attendances'], function () {
        Route::get('clear', [AttendanceController::class, 'clear']);
        Route::get('employees/summary', [AttendanceController::class, 'employeesSummary']);
        Route::get('employees', [AttendanceController::class, 'employees']);
        Route::get('logs', [AttendanceController::class, 'logs']);
        Route::get('report/{export?}', [AttendanceController::class, 'report']);
        Route::post('manual-attendances', [AttendanceController::class, 'manualAttendance']);
        Route::post('request', [AttendanceController::class, 'request']);
        Route::get('approvals', [AttendanceController::class, 'approvals']);
        Route::get('approvals/count-total', [AttendanceController::class, 'countTotalapprovals']);
        Route::get('approvals/{attendance_detail}', [AttendanceController::class, 'showApproval']);
        Route::put('bulk-approve', [AttendanceController::class, 'bulkApprove']);
        Route::put('approvals/{attendance_detail}', [AttendanceController::class, 'approve']);
        Route::put('{attendance}/restore', [AttendanceController::class, 'restore']);
        Route::delete('{attendance}/force-delete', [AttendanceController::class, 'forceDelete']);
    });
    Route::apiResource('attendances', AttendanceController::class);

    // Route::group(['prefix' => 'timeoff-policies/{timeoff_policy}'], function () {
    //     Route::post('users', [UserTimeoffPolicyController::class, 'store']);
    //     Route::delete('users/{user}', [UserTimeoffPolicyController::class, 'destroy']);
    // });
    Route::apiResource('timeoff-policies', TimeoffPolicyController::class);

    Route::get('timeoff-quotas/users', [TimeoffQuotaController::class, 'users']);
    Route::get('timeoff-quotas/me/{timeoff_policy}', [TimeoffQuotaController::class, 'meDetails']);
    Route::post('timeoff-quotas/revaluate-timeoff-discipline', [TimeoffQuotaController::class, 'revaluateTimeoffDiscipline']);
    Route::apiResource('timeoff-quotas', TimeoffQuotaController::class);

    Route::apiResource('timeoff-quota-histories', TimeoffQuotaHistoryController::class)->only('index', 'show');

    Route::get('timeoffs/approvals', [TimeoffController::class, 'approvals']);
    Route::get('timeoffs/approvals/count-total', [TimeoffController::class, 'countTotalapprovals']);
    Route::group(['prefix' => 'timeoffs/{timeoff}'], function () {
        Route::put('cancel', [TimeoffController::class, 'cancel']);
        Route::put('approve', [TimeoffController::class, 'approve']);
    });
    Route::apiResource('timeoffs', TimeoffController::class);


    Route::apiResource('overtimes', OvertimeController::class);
    Route::post('overtimes/user-settings', [OvertimeController::class, 'userSetting']);

    Route::get('overtime-requests/report', [OvertimeRequestController::class, 'report']);
    Route::get('overtime-requests/approvals', [OvertimeRequestController::class, 'approvals']);
    Route::get('overtime-requests/approvals/count-total', [OvertimeRequestController::class, 'countTotalApprovals']);
    Route::put('overtime-requests/{overtime_request}/approve', [OvertimeRequestController::class, 'approve']);
    Route::apiResource('overtime-requests', OvertimeRequestController::class)->except('update');

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
    Route::get('formulas/test', [\App\Http\Controllers\Api\FormulaController::class, 'test']);

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
    Route::get('run-payrolls/{run_payroll}/export/ocbc', [RunPayrollController::class, 'exportOcbc']);
    Route::get('run-payrolls/{run_payroll}/export/bca', [RunPayrollController::class, 'exportBca']);
    Route::delete('run-payrolls/bulk-delete', [RunPayrollController::class, 'bulkDestroy']);
    Route::apiResource('run-payrolls', RunPayrollController::class);

    Route::put('run-thrs/user-components/{run_thr_user}', [RunThrController::class, 'updateUserComponent']);
    Route::get('run-thrs/{run_thr}/export', [RunThrController::class, 'export']);
    Route::get('run-thrs/{run_thr}/export/ocbc', [RunThrController::class, 'exportOcbc']);
    Route::get('run-thrs/{run_thr}/export/bca', [RunThrController::class, 'exportBca']);
    Route::apiResource('run-thrs', RunThrController::class);

    Route::get('request-change-datas/approvals', [RequestChangeDataController::class, 'approvals']);
    Route::get('request-change-datas/approvals/count-total', [RequestChangeDataController::class, 'countTotalApprovals']);
    Route::put('request-change-datas/{request_change_data}/approve', [RequestChangeDataController::class, 'approve']);
    Route::apiResource('request-change-datas', RequestChangeDataController::class)->only(['index', 'show']);

    Route::get('request-schedules/approvals', [RequestScheduleController::class, 'approvals']);
    Route::get('request-schedules/approvals/count-total', [RequestScheduleController::class, 'countTotalApprovals']);
    Route::put('request-schedules/{request_schedule}/approve', [RequestScheduleController::class, 'approve']);
    Route::apiResource('request-schedules', RequestScheduleController::class);

    Route::put('supervisor-request-schedules/{schedule}/approve', [SupervisorRequestScheduleController::class, 'approve']);
    Route::apiResource('supervisor-request-schedules', SupervisorRequestScheduleController::class);

    Route::get('request-shifts/available-shifts', [RequestShiftController::class, 'availableShifts']);
    Route::get('request-shifts/approvals', [RequestShiftController::class, 'approvals']);
    Route::get('request-shifts/approvals/count-total', [RequestShiftController::class, 'countTotalApprovals']);
    Route::put('request-shifts/bulk-approve', [RequestShiftController::class, 'bulkApprove']);
    Route::put('request-shifts/{request_shift}/approve', [RequestShiftController::class, 'approve']);
    Route::apiResource('request-shifts', RequestShiftController::class);

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

    Route::get('incidents/export', [IncidentController::class, 'export']);
    Route::apiResource('incidents', IncidentController::class);

    // Route::get('clients/summaries', [ClientController::class, 'summary']);
    // Route::apiResource('clients', ClientController::class);
    // Route::get('client-locations/generate-qr-code', [ClientLocationController::class, 'generateQrCode']);
    // Route::apiResource('client-locations', ClientLocationController::class);

    Route::get('guest-books/export', [GuestBookController::class, 'export']);
    Route::apiResource('guest-books', GuestBookController::class);

    Route::apiResource('user-patrol-tasks', UserPatrolTaskController::class);
    Route::get('patrols/test-export', [PatrolController::class, 'testExport']);
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

    Route::post('user-patrol-batches/sync', [UserPatrolBatchController::class, 'sync']);
    Route::delete('user-patrol-batches/{user_patrol_batch}/force-delete', [UserPatrolBatchController::class, 'forceDelete']);
    Route::apiResource('user-patrol-batches', UserPatrolBatchController::class);
    Route::apiResource('user-patrol-movements', UserPatrolMovementController::class);

    Route::apiResource('npp', NppController::class);

    Route::apiResource('settings', SettingController::class);
    Route::apiResource('banks', BankController::class);
    Route::group(['prefix' => 'banks/{bank}'], function () {
        Route::put('restore', [BankController::class, 'restore']);
        Route::delete('force-delete', [BankController::class, 'forceDelete']);
    });

    Route::get('extra-offs/users', [ExtraOffController::class, 'users']);
    Route::get('extra-offs/eligible-users', [ExtraOffController::class, 'eligibleUsers']);
    Route::apiResource('extra-offs', ExtraOffController::class)->only(['index', 'show', 'store']);
    Route::apiResource('loans', LoanController::class);
    Route::apiResource('reprimands', ReprimandController::class);
    Route::apiResource('absence-reminders', AbsenceReminderController::class)->only(['index', 'show', 'update']);
    Route::delete('media/bulk-delete', [MediaController::class, 'bulkDestroy']);
    Route::apiResource('media', MediaController::class)->only(['index', 'show', 'destroy']);

    Route::get('lock-attendances/{lock_attendance}/details', [LockAttendanceController::class, 'details']);
    Route::apiResource('lock-attendances', LockAttendanceController::class);

    Route::get('reimbursement-categories/{reimbursement_category}/users', [ReimbursementCategoryController::class, 'getUsers']);
    Route::post('reimbursement-categories/{reimbursement_category}/users', [ReimbursementCategoryController::class, 'addUsers']);
    Route::put('reimbursement-categories/{reimbursement_category}/users', [ReimbursementCategoryController::class, 'editUser']);
    Route::delete('reimbursement-categories/{reimbursement_category}/users', [ReimbursementCategoryController::class, 'deleteUsers']);
    Route::apiResource('reimbursement-categories', ReimbursementCategoryController::class);

    Route::get('reimbursements/approvals', [ReimbursementController::class, 'approvals']);
    Route::get('reimbursements/approvals/count-total', [ReimbursementController::class, 'countTotalApprovals']);
    Route::put('reimbursements/{overtime_request}/approve', [ReimbursementController::class, 'approve']);
    Route::apiResource('reimbursements', ReimbursementController::class)->except('update');

    Route::get('test/generate-timeoff', [\App\Http\Controllers\Api\TestController::class, 'generateTimeoff']);
});
