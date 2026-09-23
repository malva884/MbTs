<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EhsEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'tipo_scheda', 'tipo_rilevazione', 'employee_id', 'nome', 'cognome', 'matricola',
        'qualifica', 'user_create', 'testimoni', 'data_evento', 'ora_lavorativa', 'desc_dinamica',
        'causa_id', 'azioni', 'reparto_id', 'sede_id', 'lesione_id', 's_anatomica_id',
        'giorni_infortunio', 'tipo_evento_id', 'analisi_causa', 'azioni_contenimento',
        'responsabile_azione', 'data_chiusura', 'path_drive', 'old_id',
    ];

    protected $casts = [
        'tipo_scheda' => 'integer',
        'tipo_rilevazione' => 'integer',
        'qualifica' => 'integer',
        'giorni_infortunio' => 'integer',
        'old_id' => 'integer',
        'data_evento' => 'datetime',
        'data_chiusura' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id', 'id');
    }

    public function reparto()
    {
        return $this->belongsTo(HrDepartment::class, 'reparto_id', 'id');
    }

    public function impianto()
    {
        return $this->belongsTo(EhsSite::class, 'sede_id', 'id');
    }

    public function lesione()
    {
        return $this->belongsTo(EhsInjury::class, 'lesione_id', 'id');
    }

    public function anatomica()
    {
        return $this->belongsTo(EhsAnatomical::class, 's_anatomica_id', 'id');
    }

    public function evento()
    {
        return $this->belongsTo(EhsTypeEvent::class, 'tipo_evento_id', 'id');
    }

    public function causa()
    {
        return $this->belongsTo(EhsCause::class, 'causa_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_create', 'id');
    }
}
