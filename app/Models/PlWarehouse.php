<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlWarehouse extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','marca','descrizione','tipologia','pn_interno','pn_oem','quantita_minima','quantita','data_fornitura',
        'prezzo','cpu','cpu_numero','hdd_capienza','hdd_numero','ram_numero','ram_memoria','display','wifi','wifi_tipologia','company_id'];

    static function checkQuantity($id)
    {

    }
}
