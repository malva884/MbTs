<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalUserNotification extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id', 'nome', 'attivo','email','tipologia_notifica'
    ];
}
