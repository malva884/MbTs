<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrCostCenter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','centro_di_costo','valore','disattivo'];
}
