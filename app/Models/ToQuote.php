<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToQuote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','numero','rdo','parametro','user','cliente_id','cu','data_rdo','data_preventivo','nota','company_id'];

    public function cliente_obj()
    {
        return $this->hasOne(ToClient::class,'id','cliente_id');
    }

    public function cables()
    {
        return $this->hasMany(ToQuoteCable::class, 'preventivo_id', 'id');
    }
}
