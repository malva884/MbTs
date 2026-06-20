<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRole extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','role','model','disabled'];

    public static $WfModels = ['WfOrder' => 'Commesse', 'WfVariations' => 'Variazioni',
        'WfProcedure' => 'Procedure', 'WfProcessi' => 'Processi'];
}
