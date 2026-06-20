<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrRequestPending extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id','richiesta_id','user_id','stato','nota','approvatore','livello','updated_at'
    ];
}
