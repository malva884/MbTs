<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrEmployeeAccess extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'access_resource_id',
        'permission',
        'notes',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function accessResource(): BelongsTo
    {
        return $this->belongsTo(HrAccessResource::class, 'access_resource_id');
    }
}
