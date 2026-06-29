<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id',
        'type',
        'from_location_id',
        'to_location_id',
        'performed_by',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(ItAsset::class, 'asset_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(ItLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(ItLocation::class, 'to_location_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }
}
