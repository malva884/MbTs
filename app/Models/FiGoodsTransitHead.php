<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiGoodsTransitHead extends Model
{
    use HasFactory;

    protected $fillable = ['id','user','import','storege','totale','value_cc','value_ofc','value_fkm','anno','mese','calcolato'];
}
