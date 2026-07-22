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
        'Dashboard'=>'dashboard',
        'Fibre-Tipologie'=>'fibre.tipologie',
        'Finanze-Fatturato'=>'fi.fatturato',
        'Finanze-Spedito'=>'fi.spedito',
        'Hr-Dipendenti'=>'hr.dipendenti',
        'Hr-Formazioni'=>'hr.formazioni',
        'Hr-Reparti'=>'hr.reparti',
        'Hr-Ruoli'=>'hr.ruoli',
        'Hr-Competenze'=>'hr.competenze',
        'Hr-Presenze'=>'hr.presenze',
        'Hr-Richieste'=>'hr.richieste',
        //'Hr-Report'=>'hr.report',
        'It-Assistenza'=>'it.assistenza',
        'Macchinari'=>'macchinari',
        'Permessi'=>'permessi',
        'Plant-Asset'=>'pl.asset',
        'Preventivi' =>'to.precentivi',
        'Produzione-Business-Intelligence'=>'prod.business.intelligence',
        'Produzione-Interscambio'=>'prod.interscambio',
        'Produzione-Performance'=>'prod.performance',
        'Produzione-Kpi'=>'prod.kpi',
        'Produzione-Magazzino'=>'prod.magazzino',
        'Qualita-Checker-Report'=>'qt.checker.report',
        'Qualita-Report-Rame'=>'qt.report.rame',
        'Qualita-Conformita'=>'qt.conformita',
        'Qualita-Fai'=>'qt.fai',
        'Qualita-Prove-Tipo'=>'qt.prove.tipo',
        'Qualita-ValidazioneDocumenti'=>'qt.validazione.documenti',
        'Qt-Supplier' => 'qt.supplier',
        'Reception-Register'=>'rp.register',
        'Shipping-Picking-List'=>'sp.picking.list',
        'Users'=>'user',
        //'Visitors'=>'visitor',
        //'Emploees'=>'emploee',
        'Wf-Commesse' => 'wf.commesse',
        'Wf-Procedure' => 'wf.procedure',
        'Impersonate' => 'impersonate',
    ];

    static $permission_names = [
        'admin','list','create','edit','read','import','sing','report','deleted','users'
    ];


}
