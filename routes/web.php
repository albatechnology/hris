<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NationalHolidayController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
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
    Route::resource('national-holidays', NationalHolidayController::class);
});
