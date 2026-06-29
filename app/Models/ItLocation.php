<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItLocation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'description',
        'parent_id',
        'disabled',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(ItLocation::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(ItLocation::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ItAsset::class, 'location_id');
    }
}
