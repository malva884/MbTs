<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrStockCategorie extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','condizioni','um','quantita','legenda','utenti_notifica','tag','notifica'];


}
