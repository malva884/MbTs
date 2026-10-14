<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WsMachineCost extends Model
{
    use HasFactory;

    protected $table = 'machine_labor_costs';

    protected $fillable = [
        'id', 'machine', 'cost', 'manpower_cost', 'date_import',
    ];

    protected $casts = [
        'cost' => 'float',
        'manpower_cost' => 'float',
        'date_import' => 'date',
    ];
}
