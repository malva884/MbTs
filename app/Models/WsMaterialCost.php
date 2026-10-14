<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WsMaterialCost extends Model
{
    use HasFactory;

    protected $table = 'materials_costs';

    protected $fillable = [
        'id', 'material', 'cost', 'date_import',
    ];

    protected $casts = [
        'cost' => 'float',
        'date_import' => 'date',
    ];
}
