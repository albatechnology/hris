<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NationalHolidayController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TimeoffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();
Route::group(['middleware' => ['auth', 'isSuperAdmin']], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::delete('users/mass/destroy', [UserController::class, 'massDestroy'])->name('users.mass.destroy');
    Route::resource('users', UserController::class);

    Route::delete('roles/mass/destroy', [RoleController::class, 'massDestroy'])->name('roles.mass.destroy');
    Route::resource('roles', RoleController::class);

    Route::delete('groups/mass/destroy', [GroupController::class, 'massDestroy'])->name('groups.mass.destroy');
    Route::resource('groups', GroupController::class);

    Route::delete('branches/mass/destroy', [BranchController::class, 'massDestroy'])->name('branches.mass.destroy');
    Route::resource('branches', BranchController::class);

    Route::delete('companies/mass/destroy', [CompanyController::class, 'massDestroy'])->name('companies.mass.destroy');
    Route::resource('companies', CompanyController::class);

    Route::delete('national-holidays/mass/destroy', [NationalHolidayController::class, 'massDestroy'])->name('national-holidays.mass.destroy');

    Route::delete('attendances/mass/destroy', [AttendanceController::class, 'massDestroy'])->name('attendances.mass.destroy');
    Route::resource('attendances', AttendanceController::class);

    Route::delete('timeoffs/mass/destroy', [TimeoffController::class, 'massDestroy'])->name('timeoffs.mass.destroy');
    Route::resource('timeoffs', TimeoffController::class);

    Route::delete('overtimes/mass/destroy', [OvertimeController::class, 'massDestroy'])->name('overtimes.mass.destroy');
    Route::resource('overtimes', OvertimeController::class);

    Route::post('national-holidays/import', [NationalHolidayController::class, 'import'])->name('national-holidays.import');
    Route::resource('national-holidays', NationalHolidayController::class);

    // Route::get('national-holidays-import-export', [NationalHolidayController::class, 'fileImportExport']);
    // Route::post('national-holidays-import', [NationalHolidayController::class, 'fileImport'])->name('national-holidays-import');
    // Route::get('national-holidays-export', [NationalHolidayController::class, 'fileExport'])->name('national-holidays-export');


    Route::get('exports/sample/{type}', [ExportController::class, 'sample'])->name('exports.sample');
});
