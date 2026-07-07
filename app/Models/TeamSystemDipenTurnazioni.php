<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSystemDipenTurnazioni extends Model
{
    protected $connection = 'sqlsrv_teamsystem';
    protected $table = 'dipen_turnazioni';
    protected $guarded = [];

    public $timestamps = false;

    public function turnazioneRel()
    {
        return $this->belongsTo(TeamSystemTurnazioni::class, 'turnazione', 'turnazione');
    }
}
