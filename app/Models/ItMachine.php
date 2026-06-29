<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ItMachine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'code',
        'description',
        'location_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(ItLocation::class);
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(ItAssetAssignment::class, 'assignable');
    }
}
