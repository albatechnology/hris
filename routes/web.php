<?php

use App\Http\Controllers\DashboardController;
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
});
