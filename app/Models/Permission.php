<?php
namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as OriginalPermission;
class Permission extends OriginalPermission
{

      public $guard_name = 'api';



    protected $fillable = [
        'name',
        'guard_name',
        'updated_at',
        'created_at',
    ];

    static $module_names = [
        'Users'=>'user',
        'Permessi'=>'permessi',
        'Qualita-Checker-Report'=>'qt.checker.report',
        'Qualita-Fai'=>'qt.fai',
        //'Visitors'=>'visitor',
        //'Emploees'=>'emploee',
    ];

    static $permission_names = [
        'list','create','edit','read','list','import','sing','report','notification','deleted'
    ];


}
