<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskArea extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','area','responsabile_id','sigla','cartella_drive','tipologia','nascosta','colore',
        'approvazione_task','approvazione_sub_task','notifiche'];
}
