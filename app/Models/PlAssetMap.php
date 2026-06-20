<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlAssetMap extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','map','etichetta','gruppo'];
}
