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
        'Cavi'=>'to.cavi',
        'Difetti'=>'difetti',
        'Fibre-Tipologie'=>'fibre.tipologie',
        'Finanze-Fatturato'=>'fi.fatturato',
        'Finanze-Spedito'=>'fi.spedito',
        'Hr-Richieste'=>'hr.richieste',
        'It-Assistenza'=>'it.assistenza',
        'Macchinari'=>'macchinari',
        'Permessi'=>'permessi',
        'Plant-Asset'=>'pl.asset',
        'Preventivi' =>'to.precentivi',
        'Produzione-Business-Intelligence'=>'prod.business.intelligence',
        'Produzione-Performance'=>'prod.performance',
        'Produzione-Kpi'=>'prod.kpi',
        'Produzione-Magazzino'=>'prod.magazzino',
        'Qualita-Checker-Report'=>'qt.checker.report',
        'Qualita-Report-Rame'=>'qt.report.rame',
        'Qualita-Conformita'=>'qt.conformita',
        'Qualita-Fai'=>'qt.fai',
        'Qualita-Prove-Tipo'=>'qt.prove.tipo',
        'Qt-Supplier' => 'qt.supplier',
        'Reception-Register'=>'rp.register',
        'Shipping-Picking-List'=>'sp.picking.list',
        'Users'=>'user',
        //'Visitors'=>'visitor',
        //'Emploees'=>'emploee',
    ];

    static $permission_names = [
        'admin','list','create','edit','read','import','sing','report','deleted'
    ];


}
