<?php

use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GpController;
use App\Http\Controllers\QtCheckerReportController;
use App\Http\Controllers\QtFaiController;
use App\Http\Controllers\QtConformitaController;
use App\Http\Controllers\RpRegisterActivityController;
use App\Http\Controllers\RpRegisterLogController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('roles',  [RoleController::class, 'list'] );
    Route::get('permissions',  [PermissionController::class, 'list'] );
    Route::post('permissions/store',  [PermissionController::class, 'store'] );
    Route::delete('permissions/delete/{id}',  [PermissionController::class, 'delete'] );
    Route::get('permissions/groups',  [PermissionController::class, 'groupPermissionsUsers'] );
    Route::get('permissions/user_permissions',  [PermissionController::class, 'userPermissions'] );
    Route::get('permissions/tab/{id}',  [PermissionController::class, 'list_tab'] );
    Route::post('permissions/stored',  [PermissionController::class, 'stored'] );
    Route::post('permissions/set/{id}',  [PermissionController::class, 'set_user'] );
});

Route::group(['prefix' => 'users', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('view/{id}', [UserController::class, 'view']);
    Route::get('userOnLine/{id?}', [UserController::class, 'userOnLine']);
    Route::get('usersOnline', [UserController::class, 'usersOnline']);
    Route::get('activities/{id}',  [UserController::class, 'activities'] );
    Route::get('get_users_permission', [UserController::class, 'getUsersPermission']);
    Route::get('totalUsers', [UserController::class, 'totalUsers']);
    Route::post('new', [UserController::class, 'store']);
    Route::post('edit/{id}', [UserController::class, 'update']);
    Route::post('delete/{id}', [UserController::class, 'delete']);
});

Route::group(['prefix' => 'reception', 'middleware' => 'auth:sanctum'], function () {
    Route::get('google-calendar/connect', [GoogleCalendarController::class, 'connect']);
    //Route::get('google-calendar/auth-callback', [GoogleCalendarController::class, 'store']);
    Route::get('getResources', [GoogleCalendarController::class, 'getResources']);
    Route::post('addEvent', [GoogleCalendarController::class, 'addEvent']);
    Route::put('editEvent/{id}', [GoogleCalendarController::class, 'editEvent']);

    Route::get('getRegister/{id}', [RpRegisterLogController::class, 'getRegister']);
    Route::post('storeRegister/{id}', [RpRegisterLogController::class, 'storeRegister']);
    Route::get('register/list', [RpRegisterActivityController::class, 'list']);

});

Route::group(['prefix' => 'reception'], function () {
    Route::get('google-calendar/auth-callback', [GoogleCalendarController::class, 'store']);
});

Route::group(['prefix' => 'qt', 'middleware' => 'auth:sanctum'], function () {
    Route::get('checker/report', [QtCheckerReportController::class, 'index']);
    Route::post('checker/report/store', [QtCheckerReportController::class, 'store']);
    Route::delete('checker/report/delete/{id}', [QtCheckerReportController::class, 'deleted']);

    Route::get('fai/list', [QtFaiController::class, 'index']);
    Route::post('fai/store', [QtFaiController::class, 'store']);
    Route::post('fai/closed/{id}', [QtFaiController::class, 'closed']);
    Route::delete('fai/delete/{id}', [QtFaiController::class, 'deleted']);

    Route::post('conformita/store', [QtConformitaController::class, 'store']);

});

Route::group(['prefix' => 'test', ], function () {
    Route::get('test', [GoogleCalendarController::class, 'test']);

});

Route::group(['prefix' => 'gp', 'middleware' => 'auth:sanctum'], function () {
    Route::get('getMateriale/{ol}', [GpController::class, 'getMateriale']);

});


