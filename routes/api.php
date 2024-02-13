<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\LiveAttendanceController;
use App\Http\Controllers\Api\LiveAttendanceLocationController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\TimeoffController;
use App\Http\Controllers\Api\TimeoffRegulationController;
use App\Http\Controllers\Api\TimeoffRegulationMonthController;
use App\Http\Controllers\Api\TimeoffPeriodRegulationController;
use App\Http\Controllers\Api\TimeoffPolicyController;
use App\Http\Controllers\Api\UserContactController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserScheduleController;
use App\Http\Controllers\Api\UserTimeoffPolicyController;
use App\Http\Controllers\Api\SupervisorTypeController;
use App\Http\Controllers\Api\NationalHolidayController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('token', 'login');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('users/me', [UserController::class, 'me']);
    Route::group(['prefix' => 'users/{user}'], function () {
        Route::post('detail', [UserController::class, 'detail']);
        Route::post('payroll-info', [UserController::class, 'payrollInfo']);
        Route::apiResource('experiences', UserExperienceController::class);
        Route::apiResource('educations', UserEducationController::class);
        Route::apiResource('contacts', UserContactController::class);
    });
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);

    Route::apiResource('groups', GroupController::class);
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('departments', DepartmentController::class);

    Route::apiResource('shifts', ShiftController::class);

    Route::get('schedules/today', [ScheduleController::class, 'today']);
    Route::group(['prefix' => 'schedules/{schedule}'], function () {
        Route::put('shifts', [ScheduleController::class, 'updateShifts']);
        Route::post('users', [UserScheduleController::class, 'store']);
        Route::delete('users/{user}', [UserScheduleController::class, 'destroy']);
    });
    Route::apiResource('schedules', ScheduleController::class);

    Route::group(['prefix' => 'attendances'], function () {
        Route::post('clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('clock-out', [AttendanceController::class, 'clockOut']);
    });
    Route::apiResource('attendances', AttendanceController::class)->except('store', 'update');

    Route::group(['prefix' => 'timeoff-regulations/{timeoff_regulation}'], function () {
        Route::apiResource('periods/{period}/months', TimeoffRegulationMonthController::class);
        Route::apiResource('periods', TimeoffPeriodRegulationController::class);
    });
    Route::apiResource('timeoff-regulations', TimeoffRegulationController::class);

    Route::group(['prefix' => 'timeoff-policies/{timeoff_policy}'], function () {
        Route::post('users', [UserTimeoffPolicyController::class, 'store']);
        Route::delete('users/{user}', [UserTimeoffPolicyController::class, 'destroy']);
    });
    Route::apiResource('timeoff-policies', TimeoffPolicyController::class);
    Route::apiResource('timeoffs', TimeoffController::class);
    Route::apiResource('overtimes', OvertimeController::class);

    Route::group(['prefix' => 'live-attendances/{live_attendance}'], function () {
        Route::get('users', [LiveAttendanceController::class, 'users']);
        Route::apiResource('locations', LiveAttendanceLocationController::class);
    });
    Route::apiResource('live-attendances', LiveAttendanceController::class);
    Route::apiResource('supervisor-types', SupervisorTypeController::class);
    Route::apiResource('national-holidays', NationalHolidayController::class);
});
