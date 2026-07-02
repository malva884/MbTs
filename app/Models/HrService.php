<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrService extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'service_type_id',
        'name',
        'description',
        'provider',
        'server_url',
        'domain',
        'disabled',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(HrServiceType::class, 'service_type_id');
    }

    public function employeeServices(): HasMany
    {
        return $this->hasMany(HrEmployeeService::class, 'service_id');
    }
}
