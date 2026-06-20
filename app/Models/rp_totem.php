<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rp_totem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','nome','ip_stampante','registrazione','informativa'
    ];
}
