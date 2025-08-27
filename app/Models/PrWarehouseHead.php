<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrWarehouseHead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'titolo', 'anno','mese','data_riferimento', 'totale','fkm_ofc','ckm_ofc','ckm_cc','calcolato','path_drive','corso_lavori','company_id'
    ];
}
