<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSystemRisultati extends Model
{
    protected $connection = 'sqlsrv_teamsystem';
    protected $table = 'risultati';
    protected $guarded = [];

    protected $casts = [
        'data' => 'datetime',
    ];

    public $timestamps = false;
}
