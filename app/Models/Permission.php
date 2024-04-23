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
        'Difetti'=>'difetti',
        'Fibre-Tipologie'=>'fibre.tipologie',
        'Finanze-Fatturato'=>'fi.fatturato',
        'Finanze-Spedito'=>'fi.spedito',
        'Macchinari'=>'macchinari',
        'Permessi'=>'permessi',
        'Qualita-Checker-Report'=>'qt.checker.report',
        'Qualita-Conformita'=>'qt.conformita',
        'Qualita-Fai'=>'qt.fai',
        'Qualita-Prove-Tipo'=>'qt.prove.tipo',
        'Reception-Register'=>'rp.register',
        'Users'=>'user',
        //'Visitors'=>'visitor',
        //'Emploees'=>'emploee',
    ];

    static $permission_names = [
        'admin','list','create','edit','read','import','sing','report','notification','deleted'
    ];


}
