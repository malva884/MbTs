<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiShippedHead extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = ['id','user','import','storege','totale_spedito','target_cc','target_ofc',
        'target_fkm','value_cc','value_ofc','value_fkm','anno','mese','calcolato','created_at','updated_at'];

}
