<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiTurnoverHead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','user','import','storege','totale_spedito','target_cc','target_ofc','target_ofc_ckm','target_ckm_cc',
        'target_fkm','value_cc','value_ofc','value_fkm_ofc','value_ckm_ofc','value_ckm_cc','anno','mese','calcolato'];

}
