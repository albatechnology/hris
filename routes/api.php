<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\UserContactController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('token', 'login');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('users/me', [UserController::class, 'me']);
    Route::group(['prefix' => 'users/{user}'], function () {
        Route::post('detail', [UserController::class, 'detail']);
        Route::post('payroll-info', [UserController::class, 'payrollInfo']);
        Route::resource('experiences', UserExperienceController::class)->except('create', 'edit');
        Route::resource('educations', UserEducationController::class)->except('create', 'edit');
        Route::resource('contacts', UserContactController::class)->except('create', 'edit');
    });
    Route::resource('users', UserController::class)->except('create', 'edit');
    Route::resource('roles', RoleController::class)->except('create', 'edit');

    Route::resource('groups', GroupController::class)->except('create', 'edit');
    Route::resource('companies', CompanyController::class)->except('create', 'edit');
    Route::resource('branches', BranchController::class)->except('create', 'edit');
    Route::resource('positions', PositionController::class)->except('create', 'edit');
    Route::resource('divisions', DivisionController::class)->except('create', 'edit');
    Route::resource('departments', DepartmentController::class)->except('create', 'edit');

    Route::resource('shifts', ShiftController::class)->except('create', 'edit');

    Route::get('schedules/today', [ScheduleController::class, 'today']);
    Route::group(['prefix' => 'schedules/{schedule}'], function () {
        Route::put('shifts', [ScheduleController::class, 'updateShifts']);
        Route::post('users', [UserScheduleController::class, 'store']);
        Route::delete('users/{user}', [UserScheduleController::class, 'destroy']);
    });
    Route::resource('schedules', ScheduleController::class)->except('create', 'edit');

    Route::group(['prefix' => 'attendances'], function () {
        Route::post('clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('clock-out', [AttendanceController::class, 'clockOut']);
    });
    Route::resource('attendances', AttendanceController::class)->except('create', 'edit', 'store', 'update');
});
