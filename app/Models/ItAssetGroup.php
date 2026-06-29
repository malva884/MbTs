<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ItAssetGroup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'it_asset_groups';

    protected $fillable = [
        'brand',
        'model',
        'min_stock',
    ];

    protected $casts = [
        'min_stock' => 'integer',
    ];
}
