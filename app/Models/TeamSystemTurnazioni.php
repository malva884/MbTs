<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSystemTurnazioni extends Model
{
    protected $connection = 'sqlsrv_teamsystem';
    protected $table = 'turnazioni';
    protected $primaryKey = 'turnazione';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public $timestamps = false;
}
