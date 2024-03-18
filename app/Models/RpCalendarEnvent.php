<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RpCalendarEnvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','evento_id','titolo','data_inizio','data_fine', 'eliminato'
    ];
}
