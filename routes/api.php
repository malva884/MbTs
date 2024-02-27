<?php

use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleCalendarController;
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
    Route::get('totalUsers', [UserController::class, 'totalUsers']);
    Route::post('new', [UserController::class, 'store']);
    Route::post('edit/{id}', [UserController::class, 'update']);
    Route::post('delete/{id}', [UserController::class, 'delete']);
});

Route::group(['prefix' => 'reception', 'middleware' => 'auth:sanctum'], function () {
    Route::get('google-calendar/connect', [GoogleCalendarController::class, 'connect']);
    Route::post('google-calendar/connect', [GoogleCalendarController::class, 'store']);
    Route::get('get-resource', [GoogleCalendarController::class, 'getResources']);
    Route::post('addEvent', [GoogleCalendarController::class, 'addEvent']);
});

Route::group(['prefix' => 'reception'], function () {
    Route::get('google-calendar/auth-callback', [GoogleCalendarController::class, 'store']);
});

Route::group(['prefix' => 'test', 'middleware' => 'auth:sanctum'], function () {
    Route::get('test', [GoogleCalendarController::class, 'test']);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
