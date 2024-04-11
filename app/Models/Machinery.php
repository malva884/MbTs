<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'nome', 'name_gp','lavorazione','report_gp', 'attivo'
    ];
}
