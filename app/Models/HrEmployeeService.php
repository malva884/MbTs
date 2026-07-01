<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrEmployeeService extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'service_id',
        'username',
        'email',
        'phone',
        'status',
        'activated_at',
        'expires_at',
        'notes',
        'assigned_by',
    ];

    protected $casts = [
        'activated_at' => 'date',
        'expires_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HrService::class, 'service_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }
}
