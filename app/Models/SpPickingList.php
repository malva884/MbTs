<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpPickingList extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'ordine', 'numeroLotti','company_id','created_at'
    ];
}
