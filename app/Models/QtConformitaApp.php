<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtConformitaApp extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id', 'conformitas_id', 'soluzione','user_soluzione','nota_approvazione', 'user_approvazione','data_soluzione','data_approvazione','esito'
    ];
}
