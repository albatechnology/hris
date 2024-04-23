<?php

use App\Http\Controllers\Api\AdvancedLeaveRequestController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LiveAttendanceController;
use App\Http\Controllers\Api\LiveAttendanceLocationController;
use App\Http\Controllers\Api\NationalHolidayController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\OvertimeRequestController;
use App\Http\Controllers\Api\PayrollComponentController;
use App\Http\Controllers\Api\PayrollProrateController;
use App\Http\Controllers\Api\PayrollScheduleController;
use App\Http\Controllers\Api\PayrollSettingController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RequestChangeDataAllowesController;
use App\Http\Controllers\Api\RequestChangeDataController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RunPayrollController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SupervisorTypeController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskHourController;
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
use App\Http\Controllers\Api\UserScheduleController;
use App\Http\Controllers\Api\UserTimeoffPolicyController;
use App\Http\Controllers\Api\UserPayrollInfoController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('token', 'login');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('users/me', [UserController::class, 'me']);
    Route::post('users/register', [UserController::class, 'register']);
    Route::post('users/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::group(['prefix' => 'users/{user}'], function () {
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

    Route::apiResource('shifts', ShiftController::class);

    Route::get('schedules/today', [ScheduleController::class, 'today']);
    Route::group(['prefix' => 'schedules/{schedule}'], function () {
        // Route::put('shifts', [ScheduleController::class, 'updateShifts']);
        Route::get('users', [UserScheduleController::class, 'index']);
        Route::post('users', [UserScheduleController::class, 'store']);
        Route::put('restore', [ScheduleController::class, 'restore']);
        Route::delete('force-delete', [ScheduleController::class, 'forceDelete']);
        Route::delete('users/{user}', [UserScheduleController::class, 'destroy']);
    });
    Route::apiResource('schedules', ScheduleController::class);

    Route::get('attendances/children', [AttendanceController::class, 'children']);
    Route::get('attendances/logs', [AttendanceController::class, 'logs']);
    Route::post('attendances/request', [AttendanceController::class, 'request']);
    Route::get('attendances/approvals', [AttendanceController::class, 'approvals']);
    Route::get('attendances/approvals/count-total', [AttendanceController::class, 'countTotalapprovals']);
    Route::get('attendances/approvals/{attendance_detail}', [AttendanceController::class, 'showApproval']);
    Route::put('attendances/approvals/{attendance_detail}', [AttendanceController::class, 'approve']);
    Route::apiResource('attendances', AttendanceController::class)->except('update');

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
    Route::apiResource('overtime-requests', OvertimeRequestController::class);
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

    Route::apiResource('run-payrolls', RunPayrollController::class);

    Route::get('request-change-datas/approvals', [RequestChangeDataController::class, 'approvals']);
    Route::get('request-change-datas/approvals/count-total', [RequestChangeDataController::class, 'countTotalApprovals']);
    Route::put('request-change-datas/{request_change_data}/approve', [RequestChangeDataController::class, 'approve']);
    Route::apiResource('request-change-datas', RequestChangeDataController::class)->only(['index', 'show']);

    Route::group(['prefix' => 'tasks/{task}'], function () {
        Route::get('hours/{hour}/users', [TaskHourController::class, 'users']);
        Route::post('hours/{hour}/users', [TaskHourController::class, 'addUsers']);
        Route::delete('hours/{hour}/users', [TaskHourController::class, 'deleteUsers']);
        Route::apiResource('hours', TaskHourController::class);
        Route::put('restore', [TaskController::class, 'restore']);
        Route::delete('force-delete', [TaskController::class, 'forceDelete']);
    });
    Route::apiResource('tasks', TaskController::class);
});
