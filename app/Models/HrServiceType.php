<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrServiceType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'disabled',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(HrService::class, 'service_type_id');
    }
}
