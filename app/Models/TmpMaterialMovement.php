<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmpMaterialMovement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','materiale','um','periodo','tipo','quantita','valore'];

}
