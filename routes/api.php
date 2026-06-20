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
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\GpController;
use App\Http\Controllers\HrApproverRequestController;
use App\Http\Controllers\HrCostCenterController;
use App\Http\Controllers\HrDepartmentController;
use App\Http\Controllers\HrEmployeeController;
use App\Http\Controllers\HrEmployeeTrainingMandatoryController;
use App\Http\Controllers\HrEmployeeTrainingProfessionalController;
use App\Http\Controllers\HrHoursRequestedController;
use App\Http\Controllers\HrHoursRequestedDetailController;
use App\Http\Controllers\HrTrainingController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\MachineryController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\PlAssetAssistanceController;
use App\Http\Controllers\PlAssetController;
use App\Http\Controllers\PlAssetMapController;
use App\Http\Controllers\PlAssetMapLocationController;
use App\Http\Controllers\PlAssetMapsGroupController;
use App\Http\Controllers\PlAssetMonitoringController;
use App\Http\Controllers\PlAssetTypologyController;
use App\Http\Controllers\PlWarehouseController;
use App\Http\Controllers\PrStockCategorieController;
use App\Http\Controllers\PrWarehouseHeadController;
use App\Http\Controllers\QtCategorieController;
use App\Http\Controllers\QtCheckerReportController;
use App\Http\Controllers\QtCprTestController;
use App\Http\Controllers\QtFaiController;
use App\Http\Controllers\QtConformitaController;
use App\Http\Controllers\QtSupplierCertificationController;
use App\Http\Controllers\QtSupplierController;
use App\Http\Controllers\QtTypeTestController;
use App\Http\Controllers\QtValidationController;
use App\Http\Controllers\RpRegisterActivityController;
use App\Http\Controllers\RpRegisterLogController;
use App\Http\Controllers\SpPickingListBatchController;
use App\Http\Controllers\SpPickingListController;
use App\Http\Controllers\SyFolderSharedController;
use App\Http\Controllers\SystemNotificationController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\TaskAreaController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskUesrAreaController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ToCableController;
use App\Http\Controllers\ToCableStructureController;
use App\Http\Controllers\ToCategoryController;
use App\Http\Controllers\ToCenterCostController;
use App\Http\Controllers\ToClientController;
use App\Http\Controllers\ToMaterialController;
use App\Http\Controllers\ToQuoteCableController;
use App\Http\Controllers\ToQuoteCableStructureController;
use App\Http\Controllers\ToQuoteController;
use App\Http\Controllers\ToReelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WfCategoryController;
use App\Http\Controllers\WfCertificationController;
use App\Http\Controllers\WfOfficeController;
use App\Http\Controllers\WfOrderController;
use App\Http\Controllers\WfProcedureController;
use App\Http\Controllers\WfRoleController;
use App\Http\Controllers\WfUserController;
use App\Models\PlAssetMapsGroup;
use App\Models\PrStockCategorie;
use App\Models\SyFolderShared;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
    Route::get('csrf-token', function () {
        return response()->json(['csrf_token' => csrf_token()]);
    });

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
    Route::post('impersona/{id}',  [UserController::class, 'impersona'] );
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
    Route::get('getResources', [GoogleCalendarController::class, 'showEvents']);
    Route::post('addEvent', [GoogleCalendarController::class, 'addEvent']);
    Route::put('editEvent/{id}', [GoogleCalendarController::class, 'editEvent']);



    Route::get('register/list', [RpRegisterLogController::class, 'list']);
    Route::get('register/activity/list', [RpRegisterActivityController::class, 'list']);
    Route::post('register/store', [RpRegisterLogController::class, 'store']);
    Route::post('register/update/{id}', [RpRegisterLogController::class, 'update']);
    Route::post('register/send/{id}', [RpRegisterLogController::class, 'send']);
    Route::post('register/printer/{id}', [RpRegisterLogController::class, 'printer']);
});

Route::group(['prefix' => 'reception'], function () {
    Route::get('google-calendar/auth-callback', [GoogleCalendarController::class, 'handleGoogleCallback']);
});

Route::group(['prefix' => 'google'], function () {
    Route::get('login/google/callback', [GoogleCalendarController::class, 'redirectToGoogle']);
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
        Route::put('update/{id}', [QtFaiController::class, 'update']);
        Route::get('summary/{id}', [QtFaiController::class, 'show']);
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
        Route::post('upload/{id}', [QtTypeTestController::class, 'upload']);
    });

    Route::group(['prefix' => 'prove_cpr', 'middleware' => 'auth:sanctum'], function () {
        Route::get('report/tipo', [QtCprTestController::class, 'report_tipo']);
        Route::get('list', [QtCprTestController::class, 'list']);
        Route::get('get_prove/{ol}', [QtCprTestController::class, 'get_prove']);
        Route::get('view/{id}', [QtCprTestController::class, 'view']);
        Route::post('stored', [QtCprTestController::class, 'stored']);
        Route::post('upload/{id}', [QtCprTestController::class, 'upload']);
        Route::delete('delete/{id}', [QtCprTestController::class, 'deleted']);
    });

    Route::group(['prefix' => 'supplier', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [QtSupplierController::class, 'list']);
        Route::get('rating/', [QtSupplierController::class, 'rating']);
        Route::post('stored/', [QtSupplierController::class, 'stored']);
        Route::post('update/{id}', [QtSupplierController::class, 'update']);
        Route::get('/{id}', [QtSupplierController::class, 'view']);
        Route::get('log/{id}', [QtSupplierController::class, 'log']);
        Route::get('users/{id}', [QtSupplierController::class, 'users']);
        Route::post('users/{id}/stored', [QtSupplierController::class, 'new_user']);
        Route::post('users/{id}/update/{uid}', [QtSupplierController::class, 'update_user']);
        Route::get('notice/{id}', [QtSupplierController::class, 'notice']);
        Route::post('notice/{id}/stored', [QtSupplierController::class, 'new_notice']);
        Route::delete('delete/{id}', [QtSupplierController::class, 'deleted']);
    });

    Route::group(['prefix' => 'certification', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [QtSupplierCertificationController::class, 'list']);
        Route::post('stored/', [QtSupplierCertificationController::class, 'stored']);
        Route::post('update/{id}', [QtSupplierCertificationController::class, 'update']);
        Route::get('supplier/{id}', [QtSupplierCertificationController::class, 'list_certifications']);
        Route::get('list/', [QtSupplierCertificationController::class, 'all']);
        Route::get('notSupplier/{id}', [QtSupplierCertificationController::class, 'notSupplier']);
        Route::post('stored/supplier/', [QtSupplierCertificationController::class, 'storedSupplierCertification']);
        Route::post('update/supplier/{id}', [QtSupplierCertificationController::class, 'updateSupplierCertification']);
        Route::post('approve/supplier/{id}', [QtSupplierCertificationController::class, 'approveCertification']);
        Route::get('getQuestionario/{id}', [QtSupplierCertificationController::class, 'getQuestionario']);
    });

    Route::group(['prefix' => 'document', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/pending-list', [QtValidationController::class, 'getDocumentsToValidate']);
        Route::post('/quality-approve', [QtValidationController::class, 'approveDocument']);
        Route::get('/stats', [QtValidationController::class, 'getQualityStats']);
    });

});

Route::group(['prefix' => 'system', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'folder', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [SyFolderSharedController::class, 'index']);

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
    Route::get('get_target/{id}', [FiShippedHeadController::class, 'get_target']);
    Route::delete('delete/{id}', [FiShippedHeadController::class, 'deleted']);

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
        Route::get('get_target/{id}', [FiTurnoverHeadController::class, 'get_target']);

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

    Route::group(['prefix' => 'plant', 'middleware' => 'auth:sanctum'], function () {
        Route::get('performance', [PerformanceController::class, 'performance']);
        Route::get('revenue', [PerformanceController::class, 'revenue']);
        Route::get('production', [PerformanceController::class, 'production']);
        Route::get('dispatch', [PerformanceController::class, 'dispatch']);
        Route::get('inventory', [PerformanceController::class, 'inventory']);
        Route::get('inventoryWeek', [PerformanceController::class, 'inventoryWeek']);
        Route::get('datiProduttivi', [PerformanceController::class, 'datiProduttivi']);
        Route::get('datiScreep', [PerformanceController::class, 'datiSceep']);
        Route::get('datiScreepStage', [PerformanceController::class, 'datiSceepStage']);
        Route::get('datiOoe', [PerformanceController::class, 'datiOoe']);
        Route::get('datiFtr', [PerformanceController::class, 'datiFtr']);
        Route::get('datiCapacity', [PerformanceController::class, 'datiCapacity']);
        Route::get('datiOvertime', [PerformanceController::class, 'datiOvertime']);
        Route::get('datiCosti', [PerformanceController::class, 'datiCost']);
        Route::get('labourCost', [PerformanceController::class, 'labourCost']);
        Route::get('machines', [PerformanceController::class, 'machines']);
        Route::get('downtime', [PerformanceController::class, 'downtime']);
        Route::get('speedMachine', [PerformanceController::class, 'speedMachine']);
        Route::get('movement', [PerformanceController::class, 'movement']);
        Route::get('scarti', [PerformanceController::class, 'scarti']);
    });

});


Route::group(['prefix' => 'export', ], function () {
    Route::get('test', [GoogleCalendarController::class, 'test']);
    Route::get('conformita/excel', [QtConformitaController::class, 'export']);
    Route::get('checker_report/excel', [QtCheckerReportController::class, 'export']);
    Route::get('machinesExport', [PerformanceController::class, 'machinesExport']);
    Route::get('production/bi/excel', [GpController::class, 'exportBi']);
    Route::get('production/biProduction/excel', [GpController::class, 'exportProduzione']);
    Route::post('procedure/export', [WfProcedureController::class, 'export']);

});

Route::group(['prefix' => 'notifiche_utenti_esterni', 'middleware' => 'auth:sanctum'], function () {
    Route::get('list', [ExternalUserNotificationController::class, 'list']);
    Route::post('stored', [ExternalUserNotificationController::class, 'stored']);
    Route::post('update/{id}', [ExternalUserNotificationController::class, 'update']);
    Route::delete('delete/{id}', [ExternalUserNotificationController::class, 'deleted']);
});

Route::group(['prefix' => 'terget', 'middleware' => 'auth:sanctum'], function () {
    Route::get('agp', [TargetController::class, 'list_agp']);
    Route::get('/{id}', [TargetController::class, 'list']);
    Route::post('save', [TargetController::class, 'save']);
    Route::post('edit/{id}', [TargetController::class, 'edit']);
    Route::post('ricalcola/{id}', [TargetController::class, 'ricalcola']);
    Route::post('save_agp', [TargetController::class, 'save_agp']);


});

Route::group(['prefix' => 'pr', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'magazzino', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [PrWarehouseHeadController::class, 'list']);
        Route::get('head/{id}', [PrWarehouseHeadController::class, 'head']);
        Route::get('view/{id}', [PrWarehouseHeadController::class, 'view']);
        Route::post('import', [PrWarehouseHeadController::class, 'import']);
        Route::get('magazzino', [PrWarehouseHeadController::class, 'magazzino']);
        Route::get('get_magazzono/{id?}', [PrWarehouseHeadController::class, 'get_magazzono']);
        Route::delete('delete/{id}', [PrWarehouseHeadController::class, 'deleted']);
        Route::post('reload/{id}', [PrWarehouseHeadController::class, 'reload']);
    });

    Route::group(['prefix' => 'stock', 'middleware' => 'auth:sanctum'], function () {
        Route::get('category', [PrStockCategorieController::class, 'list']);
        Route::get('materiali', [PrStockCategorieController::class, 'materiali']);
        Route::get('get_categorie', [PrStockCategorieController::class, 'get_categorie']);
        Route::post('store', [PrStockCategorieController::class, 'store']);
        Route::post('update/{id}', [PrStockCategorieController::class, 'update']);

    });
});

Route::group(['prefix' => 'gp', 'middleware' => 'auth:sanctum'], function () {
    Route::get('getMateriale/{ol}', [GpController::class, 'getMateriale']);
    Route::get('produzione/bobine', [GpController::class, 'produzioneBobine']);
    Route::get('lista_ordini/', [GpController::class, 'listaOrdini']);
    Route::get('bi', [GpController::class, 'bi_produzione']);
    Route::get('strisciate', [GpController::class, 'strisciate']);
    Route::get('datiMacchina', [GpController::class, 'DatiMacchina']);
    Route::get('dettaglioMacchina', [GpController::class, 'DettaglioMacchina']);

    Route::get('prodotti', [GpController::class, 'prodotti']);
    Route::get('fabbisogni', [GpController::class, 'fabbisogni']);
    Route::get('ordini', [GpController::class, 'ordini']);
    Route::get('produzione', [GpController::class, 'produzione']);

});

Route::group(['prefix' => 'sp', 'middleware' => 'auth:sanctum'], function () {

    Route::group(['prefix' => 'picking', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [SpPickingListController::class, 'index']);
        Route::post('stored', [SpPickingListController::class, 'stored']);
        Route::get('batch/{id}', [SpPickingListBatchController::class, 'index']);
        Route::post('batch/add/{id}', [SpPickingListBatchController::class, 'add']);
    });
});

Route::group(['prefix' => 'to', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'preventivi', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToQuoteController::class, 'list']);
        Route::post('stored', [ToQuoteController::class, 'stored']);
        Route::post('update/{id}', [ToQuoteController::class, 'update']);
        Route::post('duplicate/{id}', [ToQuoteController::class, 'duplica']);
        Route::get('view/{id}', [ToQuoteController::class, 'view']);
        Route::post('cable', [ToQuoteController::class, 'get_cavi']);
        Route::get('{id}/list', [ToQuoteCableController::class, 'list']);
        Route::post('{id}/stored', [ToQuoteCableController::class, 'stored']);
        Route::post('{id}/update/{cid}', [ToQuoteCableController::class, 'update']);
        Route::get('cable/view/{id}', [ToQuoteCableController::class, 'view']);
        Route::get('cable/view/{id}/rows', [ToQuoteCableStructureController::class, 'view']);
        Route::post('{pid}/cable/new/{cid}/row', [ToQuoteCableStructureController::class, 'stored']);
        Route::post('{pid}/cable/update/{cid}/row/{rid}', [ToQuoteCableStructureController::class, 'update']);
        Route::delete('{pid}/cable/delete/{cid}', [ToQuoteCableController::class, 'deleted']);
        Route::delete('{pid}/cable/delete/{cid}/row/{rid}', [ToQuoteCableStructureController::class, 'deleted']);
        Route::post('export/fv/{id}',  [ToQuoteController::class, 'export_green_sheet']);
        Route::delete('delete/{id}', [ToQuoteController::class, 'deleted']);
    });

    Route::group(['prefix' => 'cavi', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToCableController::class, 'list']);
        Route::get('get_list', [ToCableController::class, 'get_list']);
        Route::post('stored', [ToCableController::class, 'store']);
        Route::post('update/{id}', [ToCableController::class, 'update']);
        Route::post('duplicate/{id}', [ToCableController::class, 'duplica']);
        Route::get('view/{id}', [ToCableController::class, 'view']);
        Route::get('view/{id}/rows', [ToCableController::class, 'rows']);

        Route::post('{id}/stored', [ToCableStructureController::class, 'store']);
        Route::post('{id}/update/{rid}', [ToCableStructureController::class, 'update']);
        Route::delete('delete/{id}', [ToCableController::class, 'deleted']);
        Route::delete('{id}/delete/{rid}', [ToCableStructureController::class, 'deleted']);
        Route::get('get_diametro/{id}', [ToCableController::class, 'get_diametro']);
    });

    Route::group(['prefix' => 'categorie', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToCategoryController::class, 'list']);
        Route::get('get_list', [ToCategoryController::class, 'get_list']);
        Route::post('stored', [ToCategoryController::class, 'stored']);
        Route::post('update/{id}', [ToCategoryController::class, 'update']);
    });

    Route::group(['prefix' => 'centri', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToCenterCostController::class, 'list']);
        Route::get('get_list', [ToCenterCostController::class, 'get_list']);
        Route::post('stored', [ToCenterCostController::class, 'stored']);
        Route::post('update/{id}', [ToCenterCostController::class, 'update']);
    });

    Route::group(['prefix' => 'materiali', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToMaterialController::class, 'list']);
        Route::get('get_list', [ToMaterialController::class, 'get_list']);
        Route::post('stored', [ToMaterialController::class, 'stored']);
        Route::post('update/{id}', [ToMaterialController::class, 'update']);
    });

    Route::group(['prefix' => 'clienti', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToClientController::class, 'list']);
        Route::get('get_list', [ToClientController::class, 'get_list']);
        Route::post('stored', [ToClientController::class, 'stored']);
        Route::post('update/{id}', [ToClientController::class, 'update']);
    });

    Route::group(['prefix' => 'bobine', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [ToReelController::class, 'list']);
        Route::get('get_list', [ToReelController::class, 'get_list']);
        Route::post('stored', [ToReelController::class, 'stored']);
        Route::post('update/{id}', [ToReelController::class, 'update']);
        Route::get('get_bobina', [ToReelController::class, 'get_bobina']);
    });
});

Route::group(['prefix' => 'pl', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'asset', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [PlAssetController::class, 'list']);
        Route::get('view/{id}', [PlAssetController::class, 'view']);
        Route::get('get_list', [PlAssetController::class, 'get_list']);
        Route::post('import', [PlAssetController::class, 'import']);
        Route::post('update/{id}', [PlAssetController::class, 'update']);
        Route::get('ping', [PlAssetMapLocationController::class, 'run_ping']);
        Route::get('not_associated', [PlAssetController::class, 'get_not_associated']);
        Route::get('associated', [PlAssetController::class, 'get_associated']);
        Route::get('devices/{id}', [PlAssetController::class, 'devices']);

    });

    Route::group(['prefix' => 'map', 'middleware' => 'auth:sanctum'], function () {
        Route::get('maps', [PlAssetMapController::class, 'list']);
        Route::get('view/{id}', [PlAssetMapController::class, 'view']);
        Route::post('store', [PlAssetMapLocationController::class, 'store']);
        Route::post('store_map', [PlAssetMapController::class, 'store']);
        Route::post('update_map/{id}', [PlAssetMapController::class, 'update']);
        Route::get('asset/{id}', [PlAssetMapLocationController::class, 'list']);
        Route::get('check', [PlAssetMapLocationController::class, 'check']);
        Route::delete('deleted/{id}', [PlAssetMapController::class, 'deleted']);
        Route::delete('asset/deleted/{id}', [PlAssetMapLocationController::class, 'deleted']);

    });

    Route::group(['prefix' => 'group', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [PlAssetMapsGroupController::class, 'list']);
        Route::get('get_list', [PlAssetMapsGroupController::class, 'get_list']);
        Route::post('store', [PlAssetMapsGroupController::class, 'store']);
        Route::post('update/{id}', [PlAssetMapsGroupController::class, 'update']);
    });

    Route::group(['prefix' => 'typology', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [PlAssetTypologyController::class, 'list']);
        Route::get('get_list', [PlAssetTypologyController::class, 'get_list']);
        Route::post('store', [PlAssetTypologyController::class, 'store']);
        Route::post('update/{id}', [PlAssetTypologyController::class, 'update']);
    });

    Route::group(['prefix' => 'assistance', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list/{id?}', [PlAssetAssistanceController::class, 'list']);
        Route::get('get_list', [PlAssetAssistanceController::class, 'get_list']);
        Route::post('store', [PlAssetAssistanceController::class, 'store']);
        Route::post('update/{id}', [PlAssetAssistanceController::class, 'update']);
    });

    Route::group(['prefix' => 'monitoring', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list/{id?}', [PlAssetMonitoringController::class, 'list']);
        Route::get('list_categoria/{id}', [PlAssetMonitoringController::class, 'list_categoria']);

    });

    Route::group(['prefix' => 'warehouse', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [PlWarehouseController::class, 'list']);
        Route::get('view/{id}', [PlWarehouseController::class, 'view']);
        Route::get('getInfo/{id}', [PlWarehouseController::class, 'getInfo']);
        Route::post('store', [PlWarehouseController::class, 'store']);
        Route::post('update/{id}', [PlWarehouseController::class, 'update']);
        Route::post('storeInfo', [PlWarehouseController::class, 'storeInfo']);
        Route::post('storeQuantity/{id}', [PlWarehouseController::class, 'storeQuantity']);
        Route::post('storeProvider/{id}', [PlWarehouseController::class, 'storeProvider']);
        Route::post('register/{id}', [PlWarehouseController::class, 'register']);
        Route::post('devideAsset/{id}', [PlWarehouseController::class, 'devideAsset']);
        Route::post('deviceBroken/{id}', [PlWarehouseController::class, 'deviceBroken']);
        Route::post('deviceReturned/{id}', [PlWarehouseController::class, 'deviceReturned']);
        Route::post('deviceNota/{id}', [PlWarehouseController::class, 'deviceNota']);
    });
});

Route::group(['prefix' => 'task', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'aree', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [TaskAreaController::class, 'list']);
        Route::get('responsabile', [TaskAreaController::class, 'checkResponsabile']);
        Route::get('view/{id}', [TaskAreaController::class, 'view']);
        Route::get('users/{id}', [TaskUesrAreaController::class, 'list']);
        Route::get('get_users/{id}', [TaskUesrAreaController::class, 'getUsers']);
        Route::post('add_user/{id}', [TaskUesrAreaController::class, 'addUser']);
        Route::post('edit_user/{id}', [TaskUesrAreaController::class, 'editUser']);
        Route::delete('del_user/{id}', [TaskUesrAreaController::class, 'delUser']);
        Route::post('get_users/{id}', [TaskUesrAreaController::class, 'getUsers']);
        Route::post('store', [TaskAreaController::class, 'store']);
        Route::post('update/{id}', [TaskAreaController::class, 'update']);
    });

    Route::group(['prefix' => 'impostazioni', 'middleware' => 'auth:sanctum'], function () {
        Route::post('update/{id}', [TaskAreaController::class, 'update_set']);
    });


    Route::get('user/{id}', [TaskController::class, 'user']);
    Route::get('statistiche', [TaskController::class, 'statistiche']);
    Route::get('list/{id}', [TaskController::class, 'list']);
    Route::get('sub_task_list/{id}', [TaskController::class, 'sub_task_list']);
    Route::get('users_task_list/{id}', [TaskController::class, 'users_task_list']);
    Route::post('{id}/setUsers', [TaskController::class, 'setUsers']);
    Route::get('mylist', [TaskController::class, 'mylist']);
    Route::get('dashboard/approvare', [TaskController::class, 'listaDaApprovare']);
    Route::get('dashboard/aggiornati', [TaskController::class, 'listaAggiornati']);
    Route::post('store', [TaskController::class, 'store']);
    Route::post('store_sub_task', [TaskController::class, 'store_sub_task']);
    Route::post('update_sub_task/{id}', [TaskController::class, 'update_sub_task']);
    Route::post('approvazione/{id}', [TaskController::class, 'approvazioneTask']);
    Route::post('update/{id}', [TaskController::class, 'update']);
    Route::post('{id}/avanzamento', [TaskController::class, 'avanzamento']);
    Route::get('{id}/task_log', [TaskController::class, 'task_log']);
    Route::post('task_nota/{id}', [TaskController::class, 'nuovaNota']);
    Route::post('uploadFile/{id}', [TaskController::class, 'uploadFile']);
    Route::get('task_nota/{id}', [TaskController::class, 'noteTask']);
    Route::post('notaScadenza/{id}', [TaskController::class, 'notaScadenza']);
});

Route::group(['prefix' => 'hr', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'requests', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [HrHoursRequestedController::class, 'list']);
        Route::get('index', [HrHoursRequestedController::class, 'all']);
        Route::get('view/{id}', [HrHoursRequestedController::class, 'view']);
        Route::get('list_off/{id}', [HrHoursRequestedDetailController::class, 'listUserOff']);
        Route::get('log/{id}', [HrHoursRequestedController::class, 'log']);
        Route::get('get_emploee', [HrHoursRequestedController::class, 'get_emploee']);
        Route::post('save/{id}', [HrHoursRequestedController::class, 'save']);
    });

    Route::group(['prefix' => 'approvatori', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [HrApproverRequestController::class, 'list']);
        Route::get('get_centro', [HrApproverRequestController::class, 'get_centro']);
        Route::post('store', [HrApproverRequestController::class, 'store']);
        Route::post('update/{id}', [HrApproverRequestController::class, 'update']);

    });

    Route::group(['prefix' => 'centro_di_costo', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [HrCostCenterController::class, 'list']);
        Route::get('get_list', [HrCostCenterController::class, 'get_list']);
        Route::post('store', [HrCostCenterController::class, 'store']);
        Route::post('update/{id}', [HrCostCenterController::class, 'update']);

    });

    Route::group(['prefix' => 'dipendenti', 'middleware' => 'auth:sanctum'], function () {
        Route::get('list', [HrEmployeeController::class, 'list']);
        Route::post('store', [HrEmployeeController::class, 'store']);
        Route::post('update/{id}', [HrEmployeeController::class, 'update']);
        Route::get('view/{id}', [HrEmployeeController::class, 'view']);
        Route::get('get_dipendenti', [HrEmployeeController::class, 'get_dipendenti']);
    });

    Route::group(['prefix' => 'formazioni', 'middleware' => 'auth:sanctum'], function () {
        Route::get('professionali', [HrEmployeeTrainingProfessionalController::class, 'list']);
        Route::post('professionali/store', [HrEmployeeTrainingProfessionalController::class, 'store']);
        Route::post('professionali/upload', [HrEmployeeTrainingProfessionalController::class, 'upload']);
        Route::delete('professionali/deleted/{id}', [HrEmployeeTrainingProfessionalController::class, 'deleted']);
        Route::get('obbligatori', [HrEmployeeTrainingMandatoryController::class, 'list']);
        Route::post('obbligatori/store', [HrEmployeeTrainingMandatoryController::class, 'store']);
    });

    Route::group(['prefix' => 'gestione', 'middleware' => 'auth:sanctum'], function () {
        Route::get('formazioni/list', [HrTrainingController::class, 'list']);
        Route::get('formazioni/get_list', [HrTrainingController::class, 'get_list']);
        Route::post('formazioni/store', [HrTrainingController::class, 'store']);
        Route::post('formazioni/update/{id}', [HrTrainingController::class, 'update']);
    });

    Route::group(['prefix' => 'reparti', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [HrDepartmentController::class, 'list']);
        Route::get('getList', [HrDepartmentController::class, 'getList']);

    });

});

Route::group(['prefix' => 'workflow', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'commesse', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfOrderController::class, 'list']);
        Route::post('store', [WfRoleController::class, 'store']);
        Route::get('document/{id}', [WfOrderController::class, 'getDocument']);
        Route::post('approval', [WfOrderController::class, 'approval']);
        Route::post('userOpenFile/{id}', [WfOrderController::class, 'userOpenFile']);
        Route::post('printFile/{id}', [WfOrderController::class, 'print']);
    });

    Route::group(['prefix' => 'procedure', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfProcedureController::class, 'list']);
        Route::get('/view/{id}', [WfProcedureController::class, 'view']);
        Route::get('/allegati/{id}', [WfProcedureController::class, 'allegati']);
        Route::post('stored', [WfProcedureController::class, 'stored']);
        Route::post('edit/{id}', [WfProcedureController::class, 'edit']);
        Route::post('stored/{id}', [WfProcedureController::class, 'storedAllegati']);
        Route::get('get/{id}', [WfProcedureController::class, 'getItem']);
        Route::get('documents/{id}', [WfProcedureController::class, 'getDocument']);
        Route::post('approval', [WfProcedureController::class, 'approval']);
        Route::post('userOpenFile/{id}', [WfProcedureController::class, 'userOpenFile']);
        Route::post('printFile/{id}', [WfProcedureController::class, 'print']);
        Route::get('get_processi', [WfProcedureController::class, 'get_processi']);
        Route::get('get_categorie', [WfProcedureController::class, 'get_categorie']);
    });

    Route::group(['prefix' => 'categorie', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfCategoryController::class, 'list']);
        Route::post('store', [WfCategoryController::class, 'store']);
        Route::post('update/{id}', [WfCategoryController::class, 'update']);
    });

    Route::group(['prefix' => 'certificazioni', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfCertificationController::class, 'list']);
        Route::post('store', [WfCertificationController::class, 'store']);
        Route::post('update/{id}', [WfCertificationController::class, 'update']);
        Route::get('get_list', [WfCertificationController::class, 'get_list']);
    });

    Route::group(['prefix' => 'office', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfOfficeController::class, 'list']);
        Route::post('store', [WfOfficeController::class, 'store']);
        Route::post('update/{id}', [WfOfficeController::class, 'update']);
        Route::get('get_list', [WfOfficeController::class, 'get_list']);
    });

    Route::group(['prefix' => 'ruoli', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfRoleController::class, 'list']);
        Route::post('store', [WfRoleController::class, 'store']);
    });

    Route::group(['prefix' => 'utenti', 'middleware' => 'auth:sanctum'], function () {
        Route::get('/', [WfUserController::class, 'list']);
        Route::post('store', [WfUserController::class, 'store']);
        Route::post('update/{id}', [WfUserController::class, 'update']);
    });

    Route::get('getModel', [WfRoleController::class, 'getModel']);
    Route::get('getRoles', [WfRoleController::class, 'getRole']);
    Route::get('is_approver', [WfUserController::class, 'is_approver']);



});

Route::group(['prefix' => 'template', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'quality', 'middleware' => 'auth:sanctum'], function () {
        Route::get('strumenti', [TemplateController::class, 'qtStrumenti']);

    });
});

Route::get('/clear', function() {
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('route:cache');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('optimize:clear');
    return 'DONE'; //Return anything
});

Route::get('auth/google', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.auth');
Route::get('auth/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('emails', [GoogleController::class, 'listMessages'])->name('emails.index');
Route::post('asset/monitoring/{serial}', [PlAssetMonitoringController::class, 'monitoring']);
Route::post('asset/register', [PlAssetController::class, 'register']);
Route::post('asset/open/assistance', [PlAssetAssistanceController::class, 'open']);

Route::get('auth2/google', [GoogleCalendarController::class, 'redirectToGoogle']);
Route::get('auth2/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback']);
Route::get('google/event', [GoogleCalendarController::class, 'showEvents']);
Route::get('getUsers',  [UserController::class, 'getUsers'] );
Route::get('getReferenti',  [RpRegisterLogController::class, 'getReferenti'] );
Route::get('register/searchVisitor', [RpRegisterLogController::class, 'searchVisitor']);
Route::post('register/store', [RpRegisterLogController::class, 'store']);
Route::post('register/totem/auth_setting', [RpRegisterLogController::class, 'auth_setting']);
Route::get('/totemList', [RpRegisterLogController::class, 'totemList']);
Route::get('/generate-text', [GeminiController::class, 'generate']);
Route::get('getRegister', [RpRegisterLogController::class, 'getRegister']);
Route::post('reception/storeRegister', [RpRegisterLogController::class, 'storeRegister']);
Route::post('notifiche/post', [SystemNotificationController::class, 'sendNotify']);
Route::POST('file', [WfOrderController::class, 'file']);

Route::get('/text', [UserController::class, 'test']);

Route::impersonate();


