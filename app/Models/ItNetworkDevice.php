<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItNetworkDevice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id',
        'ip_address',
        'mac_address',
        'device_type',
        'location',
        'rack_position',
        'vlan',
        'subnet',
        'notes',
        'disabled',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(ItAsset::class, 'asset_id');
    }
}
