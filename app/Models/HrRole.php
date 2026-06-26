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
        'tipo',
        'disattivo'
    ];

    public function employees()
    {
        return $this->belongsToMany(HrEmployee::class, 'hr_employee_role', 'role_id', 'employee_id');
    }

    public function activities()
    {
        return $this->belongsToMany(HrCompetencyActivity::class, 'hr_competency_activity_role', 'hr_role_id', 'activity_id')
            ->withPivot('valutazione_ideale');
    }
}
