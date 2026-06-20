<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrMovement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','materiale','quantita','importo','um','lotto','plant','posizione_archiviazione','tipo_movimento',
        'special_stock','documento_materiale','data_pubblicazione','data_documento','data_inserimento','testo_movimento',
        'user'];

}
