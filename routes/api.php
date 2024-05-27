<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DefectController;
use App\Http\Controllers\ExternalUserNotificationController;
use App\Http\Controllers\FiberTypeController;
use App\Http\Controllers\FiGoodsTransitHeadController;
use App\Http\Controllers\FiGoodsTransitRowController;
use App\Http\Controllers\FiShippedHeadController;
use App\Http\Controllers\FiShippedRowController;
use App\Http\Controllers\FiTurnoverHeadController;
use App\Http\Controllers\FiTurnoverRowController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GpController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\MachineryController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\QtCategorieController;
use App\Http\Controllers\QtCheckerReportController;
use App\Http\Controllers\QtFaiController;
use App\Http\Controllers\QtConformitaController;
use App\Http\Controllers\QtTypeTestController;
use App\Http\Controllers\RpRegisterActivityController;
use App\Http\Controllers\RpRegisterLogController;
use App\Http\Controllers\SystemNotificationController;
use App\Http\Controllers\TargetController;
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
    Route::get('getUsers',  [UserController::class, 'getUsers'] );
    Route::post('new', [UserController::class, 'store']);
    Route::post('edit/{id}', [UserController::class, 'update']);
    Route::post('reset_password/{id}', [UserController::class, 'reset_password']);
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
    Route::get('register/list', [RpRegisterLogController::class, 'list']);
    Route::get('register/activity/list', [RpRegisterActivityController::class, 'list']);
    Route::post('register/store', [RpRegisterLogController::class, 'store']);
    Route::post('register/send/{id}', [RpRegisterLogController::class, 'send']);
});

Route::group(['prefix' => 'reception'], function () {
    Route::get('google-calendar/auth-callback', [GoogleCalendarController::class, 'store']);
});

Route::group(['prefix' => 'qt', 'middleware' => 'auth:sanctum'], function () {

    Route::group(['prefix' => 'categorie', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [QtCategorieController::class, 'list']);
        Route::get('get_categorie', [QtCategorieController::class, 'get_categorie']);
        Route::post('store', [QtCategorieController::class, 'store']);
        Route::post('update/{id}', [QtCategorieController::class, 'update']);
    });

    Route::group(['prefix' => 'checker', 'middleware' => 'auth:sanctum'], function () {
        Route::get('report/stage', [QtCheckerReportController::class, 'report_stage']);
        Route::get('report', [QtCheckerReportController::class, 'index']);
        Route::post('report/store', [QtCheckerReportController::class, 'store']);
        Route::delete('report/delete/{id}', [QtCheckerReportController::class, 'deleted']);
    });

    Route::group(['prefix' => 'fai', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [QtFaiController::class, 'index']);
        Route::get('get_fai', [QtFaiController::class, 'get_fai']);
        Route::post('store', [QtFaiController::class, 'store']);
        Route::post('closed/{id}', [QtFaiController::class, 'closed']);
        Route::delete('delete/{id}', [QtFaiController::class, 'deleted']);

    });

    Route::group(['prefix' => 'conformita', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [QtConformitaController::class, 'index']);
        Route::get('/{id}', [QtConformitaController::class, 'view']);
        Route::post('store', [QtConformitaController::class, 'store']);
        Route::post('edit/{id}', [QtConformitaController::class, 'update']);
        Route::post('closed/{id}', [QtConformitaController::class, 'closed']);
        Route::delete('delete/{id}', [QtConformitaController::class, 'deleted']);
        Route::get('get_attivita/{id}', [QtConformitaController::class, 'get_attivita']);

    });

    Route::group(['prefix' => 'prove_tipo', 'middleware' => 'auth:sanctum'], function () {
        Route::get('report/tipo', [QtTypeTestController::class, 'report_tipo']);
        Route::get('list', [QtTypeTestController::class, 'list']);
        Route::get('get_prove/{ol}', [QtTypeTestController::class, 'get_prove']);
        Route::get('view/{id}', [QtTypeTestController::class, 'view']);
        Route::post('stored', [QtTypeTestController::class, 'stored']);
    });

});

Route::group(['prefix' => 'account', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [AccountController::class, 'account']);
    Route::post('update', [AccountController::class, 'update']);
    Route::post('changePassword', [AccountController::class, 'changePassword']);
});

Route::group(['prefix' => 'macchine', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [MachineryController::class, 'list']);
    Route::get('get_list', [MachineryController::class, 'get_list']);
    Route::post('store', [MachineryController::class, 'store']);
    Route::post('update/{id}', [MachineryController::class, 'update']);
});

Route::group(['prefix' => 'difetti', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [DefectController::class, 'list']);
    Route::get('get_list', [DefectController::class, 'get_list']);
    Route::post('store', [DefectController::class, 'store']);
    Route::post('update/{id}', [DefectController::class, 'update']);
});

Route::group(['prefix' => 'fibra_tipologia', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [FiberTypeController::class, 'list']);
    Route::get('get_list', [FiberTypeController::class, 'get_list']);
    Route::post('store', [FiberTypeController::class, 'store']);
    Route::post('update/{id}', [FiberTypeController::class, 'update']);
});

Route::group(['prefix' => 'fi', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [FiShippedHeadController::class, 'list']);
    Route::post('import', [FiShippedHeadController::class, 'import']);
    Route::get('rows/list/{id}', [FiShippedRowController::class, 'list']);
    Route::get('getTarghet', [FiShippedHeadController::class, 'getTarghet']);
    Route::get('get_clienti', [FiTurnoverRowController::class, 'get_clienti']);

    Route::group(['prefix' => 'goods_transit', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [FiGoodsTransitHeadController::class, 'list']);
        Route::post('import', [FiGoodsTransitHeadController::class, 'import']);
        Route::get('rows/list/{id}', [FiGoodsTransitRowController::class, 'list']);
    });

    Route::group(['prefix' => 'turnover', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [FiTurnoverHeadController::class, 'list']);
        Route::get('getTarghet', [FiTurnoverHeadController::class, 'getTarghet']);
        Route::post('import', [FiTurnoverHeadController::class, 'import']);
        Route::get('rows/list', [FiTurnoverRowController::class, 'list']);
        Route::get('reprot', [FiTurnoverRowController::class, 'report']);
        Route::get('check/list', [FiTurnoverRowController::class, 'check']);
        Route::post('quantita/{id}', [FiTurnoverRowController::class, 'set_quantita']);
        Route::delete('delete/{id}', [FiTurnoverHeadController::class, 'deleted']);
        Route::get('report/clienti', [FiTurnoverRowController::class, 'clienti']);
        Route::get('cavi/list/{id}', [FiTurnoverRowController::class, 'get_cavi']);
    });

});

Route::group(['prefix' => 'notifiche', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [SystemNotificationController::class, 'index']);
    Route::get('get_notifiche', [SystemNotificationController::class, 'get_notifiche']);
    Route::post('store', [SystemNotificationController::class, 'store']);
    Route::post('update/{id}', [SystemNotificationController::class, 'update']);
});

Route::group(['prefix' => 'production', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'performance', 'middleware' => 'auth:sanctum'], function () {
        Route::get('report', [PerformanceController::class, 'report']);
    });

    Route::group(['prefix' => 'kpi', 'middleware' => 'auth:sanctum'], function () {
        Route::get('report', [KpiController::class, 'report']);
    });

});


Route::group(['prefix' => 'export', ], function () {
    Route::get('test', [GoogleCalendarController::class, 'test']);
    Route::get('conformita/excel', [QtConformitaController::class, 'export']);

});

Route::group(['prefix' => 'notifiche_utenti_esterni', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [ExternalUserNotificationController::class, 'list']);
    Route::post('stored', [ExternalUserNotificationController::class, 'stored']);
    Route::post('update/{id}', [ExternalUserNotificationController::class, 'update']);
    Route::delete('delete/{id}', [ExternalUserNotificationController::class, 'deleted']);
});

Route::group(['prefix' => 'terget', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/{id}', [TargetController::class, 'list']);
    Route::post('save', [TargetController::class, 'save']);
    Route::post('edit/{id}', [TargetController::class, 'edit']);
    Route::post('ricalcola/{id}', [TargetController::class, 'ricalcola']);

});

Route::group(['prefix' => 'gp', 'middleware' => 'auth:sanctum'], function () {
    Route::get('getMateriale/{ol}', [GpController::class, 'getMateriale']);

});


