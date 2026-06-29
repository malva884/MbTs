<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrCompetencyActivity extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_competency_activities';

    protected $fillable = [
        'id',
        'attivita',
        'disattivo',
    ];

    public function roles()
    {
        return $this->belongsToMany(HrRole::class, 'hr_competency_activity_role', 'activity_id', 'hr_role_id')
            ->withPivot('valutazione_ideale');
    }

    public function evaluations()
    {
        return $this->hasMany(HrCompetencyEvaluation::class, 'activity_id', 'id');
    }
}
