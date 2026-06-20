<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToCable extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','codice','categoria_id','categoria','descrizione','norma','disattivo','company_id','created_at'];

    public function categoria_obj()
    {
        return $this->hasOne(ToCategory::class,'id','categoria_id');
    }

}
