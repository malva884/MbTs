<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','categoria','model','folder_drive','descrizione'];
}
