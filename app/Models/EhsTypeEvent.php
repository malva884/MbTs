<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EhsTypeEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'event', 'disattivo', 'old_id'];
}
