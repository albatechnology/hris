<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\UserController;
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
    Route::group(['prefix' => 'users'], function () {
        Route::get('me', [UserController::class, 'me']);
    });
    Route::resource('users', UserController::class)->except('create', 'edit');

    Route::resource('groups', GroupController::class)->except('create', 'edit');
    Route::resource('companies', CompanyController::class)->except('create', 'edit');
    Route::resource('branches', BranchController::class)->except('create', 'edit');
    Route::resource('positions', PositionController::class)->except('create', 'edit');
    Route::resource('divisions', DivisionController::class)->except('create', 'edit');
    Route::resource('departments', DepartmentController::class)->except('create', 'edit');
});
