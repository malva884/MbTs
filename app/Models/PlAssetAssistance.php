<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlAssetAssistance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','asset_id','numero_segnalazione','utente','task_id','motivazione','soluzione','stato','user_id','created_at'];
}
