<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrAccessResource extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'access_type_id',
        'name',
        'path',
        'drive_file_id',
        'server_ip',
        'import_method',
        'description',
        'disabled',
        'default_permission',
    ];

    protected $casts = [
        'disabled' => 'boolean',
    ];

    public function accessType(): BelongsTo
    {
        return $this->belongsTo(HrAccessType::class, 'access_type_id');
    }

    public function employeeAccesses(): HasMany
    {
        return $this->hasMany(HrEmployeeAccess::class, 'access_resource_id');
    }
}
