<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItNetworkDeviceLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'network_device_id',
        'status',
        'response_time_ms',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function networkDevice(): BelongsTo
    {
        return $this->belongsTo(ItNetworkDevice::class, 'network_device_id');
    }
}
