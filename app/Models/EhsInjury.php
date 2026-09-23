<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EhsInjury extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'injurie', 'disattivo', 'old_id'];
}
