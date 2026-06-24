<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrRole extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_roles';

    protected $fillable = [
        'id',
        'ruolo',
        'disattivo'
    ];

    public function employees()
    {
        return $this->belongsToMany(HrEmployee::class, 'hr_employee_role', 'role_id', 'employee_id');
    }
}
