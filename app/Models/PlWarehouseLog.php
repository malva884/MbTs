<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlWarehouseLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','employees_id','magazzino_id','quantita','descrizione','data','ritirato',
        'data_ritirato','dismesso','data_dismesso','company_id','tipo_rete','anydesk_alias','interno',
        'hostName','ip_address','sistema_operativo','sap_codice_asset','numero_seriale','fine_garanzia',
        'stato_monitoraggio','ultimo_aggiornamento_stato','monitoraggio_attivo','codice_asset',
    ];

    static function stored($employeesId, $werehouseId, $quantita, $info)
    {
        for($i=1;$i <= $quantita; $i++){
            $log = new PlWarehouseLog();
            $log->employees_id = $employeesId;
            $log->magazzino_id = $werehouseId;
            $log->quantita = 1;
            $log->tipo_rete = $info;
            $log->anydesk_alias = $info;
            $log->interno = $info;
            $log->hostName = $info;
            $log->ip_address = $info;
            $log->sistema_operativo = $info;
            $log->sap_codice_asset = $info;
            $log->numero_seriale = $info;
            $log->fine_garanzia = $info;
            $log->stato_monitoraggio = $info;
            $log->ultimo_aggiornamento_stato = $info;
            $log->monitoraggio_attivo = $info;
            $log->codice_asset = $info;
            $log->data = date('Y-m-d');
            $log->save();
        }
    }
}
