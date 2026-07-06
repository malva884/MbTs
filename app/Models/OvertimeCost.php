<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'cost_center_group',
        'week_number',
        'hours',
        'cost',
    ];
}
