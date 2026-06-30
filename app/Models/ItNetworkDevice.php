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
        'monitor_enabled',
        'status',
        'last_check_at',
        'last_online_at',
        'response_time_ms',
        'uptime_percentage',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'monitor_enabled' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(ItAsset::class, 'asset_id');
    }
}
