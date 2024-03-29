<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtConformita extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id', 'report_id', 'user','ol','bobina', 'physical_l', 'optical_l', 'stage','macchina','difetto','fibre','soluzione','time',
        'chiuso','note','diametro','num_fo','tipologia_fibra','tipologia_difetto','operator','data_apertura','data_chiusura','numero',
        'created_at','anno','materiale','google_drive_id'
    ];

    public function macchinary()
    {
        return $this->hasOne(Machinery::class,"id","macchina");
    }

    public function defect()
    {
        return $this->hasOne(Defect::class, "id", "difetto");
    }

    public function fiberTipe()
    {
        return $this->hasOne(FiberType::class,"id","tipologia_fibra");
    }
}
