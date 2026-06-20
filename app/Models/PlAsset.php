<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlAsset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','codice_asset','nazione','stato','condizione_asset','utilizzo','emp_id','utente','email','data_allocazione','scopo','tipo_allocazione',
        'data_dismesso','motivazione_dismesso','hostName','nome_utente_effetivo','tipo_asset','cpu','cpu_numero','hdd_capienza','hdd_numero','fattura_dt','fattura_numero',
        'ip_address','ultima_data_allocazione','marca','modello','mause','tipo_rete','sistema_operativo','ram_numero','ram_memoria','sap_codice_asset','numero_seriale',
        'fine_garanzia','ultima_data_allocazione','registrato','anydesk_alias','stato_monitoraggio','monitoraggio_attivo','ultimo_aggiornamento_stato','tag_asset','ultima_notifica'];
}
