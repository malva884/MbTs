<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItAsset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id',
        'location_id',
        'serial_number',
        'asset_tag',
        'brand',
        'model',
        'purchase_date',
        'product_link',
        'unit_cost',
        'warranty_expiry',
        'quantity',
        'status',
        'notes',
        'disabled',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItCategory::class, 'category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ItLocation::class, 'location_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ItAssetAssignment::class, 'asset_id');
    }

    public function networkDevice()
    {
        return $this->hasOne(ItNetworkDevice::class, 'asset_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ItTransaction::class, 'asset_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(ItSupplier::class, 'it_asset_supplier', 'asset_id', 'supplier_id')
            ->withPivot('unit_cost', 'purchase_date', 'order_reference', 'notes', 'product_link')
            ->withTimestamps();
    }
}
