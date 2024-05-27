<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SystemNotification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','user','nome','email','notifica','attivo'
    ];

    public static $notifiche = [
        'fi_fatturato_giornaliero' => 'Fatturato Giornaliero',
        'fi_fatturato_mensile' => 'Fatturato Mensile',
        'fi_spedito_giornaliero' => 'Spedito Giornaliero',
        'fi_spedito_mensile' => 'Spedito Mensile',
        'qt_non_conformita_settimale' => 'Non Conforità Settimanale',
        'qt_non_conformita_mensile' => 'Non Conforità Mensile',
        'qt_non_conformita_apertura' => 'Non Conforità Apertura',
        'qt_non_conformita_approvazione' => 'Non Conforità Approvazione',
        'qt_prove_tipo_giornalienro' => 'Prove Di Tipo Giornaliero',
        'qt_prove_tipo_mensile' => 'Prove Di Tipo Mensile',
    ];
}
