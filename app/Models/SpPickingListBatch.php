<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpPickingListBatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id', 'picking_id', 'ordine','lotto','materiale','um','giacenza','company_id'
    ];
}
