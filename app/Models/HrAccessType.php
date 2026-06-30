<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrAccessType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'disabled',
    ];

    protected $casts = [
        'disabled' => 'boolean',
    ];

    public function resources(): HasMany
    {
        return $this->hasMany(HrAccessResource::class, 'access_type_id');
    }
}
