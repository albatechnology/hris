<?php

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
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SupervisorTypeController;
use App\Http\Controllers\Api\TimeoffController;
use App\Http\Controllers\Api\TimeoffPeriodRegulationController;
use App\Http\Controllers\Api\TimeoffPolicyController;
use App\Http\Controllers\Api\TimeoffRegulationController;
use App\Http\Controllers\Api\TimeoffRegulationMonthController;
use App\Http\Controllers\Api\UserContactController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserCustomFieldController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserEventController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserScheduleController;
use App\Http\Controllers\Api\UserTimeoffPolicyController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('token', 'login');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('users/me', [UserController::class, 'me']);
    Route::group(['prefix' => 'users/{user}'], function () {
        Route::get('companies', [UserController::class, 'companies']);
        Route::get('branches', [UserController::class, 'branches']);
        Route::post('detail', [UserController::class, 'detail']);
        Route::post('payroll-info', [UserController::class, 'payrollInfo']);
        Route::post('upload-photo', [UserController::class, 'uploadPhoto']);
        Route::apiResource('experiences', UserExperienceController::class);
        Route::apiResource('educations', UserEducationController::class);
        Route::apiResource('contacts', UserContactController::class);
        Route::apiResource('custom-fields', UserCustomFieldController::class)->except('destroy');
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

    Route::post('attendances/request', [AttendanceController::class, 'request']);
    Route::get('attendances/approvals', [AttendanceController::class, 'approvals']);
    Route::get('attendances/approvals/{attendance_detail}', [AttendanceController::class, 'showApproval']);
    Route::put('attendances/approvals/{attendance_detail}', [AttendanceController::class, 'approve']);
    Route::apiResource('attendances', AttendanceController::class)->except('update');

    Route::group(['prefix' => 'timeoff-policies/{timeoff_policy}'], function () {
        Route::post('users', [UserTimeoffPolicyController::class, 'store']);
        Route::delete('users/{user}', [UserTimeoffPolicyController::class, 'destroy']);
    });
    Route::apiResource('timeoff-policies', TimeoffPolicyController::class);

    Route::get('timeoffs/approvals', [TimeoffController::class, 'approvals']);
    Route::group(['prefix' => 'timeoffs/{timeoff}'], function () {
        Route::put('approve', [TimeoffController::class, 'approve']);
    });
    Route::apiResource('timeoffs', TimeoffController::class);

    Route::apiResource('payroll-components', PayrollComponentController::class);

    Route::apiResource('overtimes', OvertimeController::class);
    Route::post('overtimes/user-settings', [OvertimeController::class, 'userSetting']);

    Route::get('overtime-requests/approvals', [OvertimeRequestController::class, 'approvals']);
    Route::apiResource('overtime-requests', OvertimeRequestController::class);
    Route::put('overtime-requests/{overtime_request}/approve', [OvertimeRequestController::class, 'approve']);

    Route::group(['prefix' => 'live-attendances/{live_attendance}'], function () {
        Route::get('users', [LiveAttendanceController::class, 'users']);
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
    Route::put('notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
});
