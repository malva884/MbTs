<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItSupplier extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'vat_number',
        'notes',
        'disabled',
    ];

    protected $casts = [
        'disabled' => 'boolean',
    ];

    public function assets()
    {
        return $this->belongsToMany(ItAsset::class, 'it_asset_supplier', 'supplier_id', 'asset_id')
            ->withPivot('unit_cost', 'purchase_date', 'order_reference', 'notes', 'product_link')
            ->withTimestamps();
    }
}
