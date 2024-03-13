<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QtCheckerReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','ol','user','date_create','num_fo','coil', 'fo_try', 'stage', 'not_conformity','note'
    ];
}
