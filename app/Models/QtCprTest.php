<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtCprTest extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id', 'ol', 'user','materiale','descrizione', 'esito', 'standard', 'specifica','tipo','data_prova','path_drive','cliente','note','created_at','class'
    ];

    public function categoriaTipo()
    {
        return $this->hasOne(QtCategorie::class,'id', 'tipo');
    }

}
