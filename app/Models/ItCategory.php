<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'disabled',
        'require_label',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(ItCategory::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(ItCategory::class, 'parent_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ItAsset::class, 'category_id');
    }
}
