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
        'stato','note','diametro','num_fo','tipologia_fibra','tipologia_difetto','operator','data_apertura','data_chiusura','numero',
        'created_at','anno','materiale','google_drive_id','ottico','rame','ftr_ottico','ftr_rame','motivazione_chiusura','motivazione_chiusura_text'
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

    public function stato_label()
    {
        switch ($this->stato) {
            case 1:
                return "Aperto";
                break;
            case 2:
                return "In Attesa di chiusura";
                break;
            case 3:
                return "Chiuso";
                break;
        }

    }
}
