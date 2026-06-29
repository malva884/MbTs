<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrCompetencyEvaluation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_competency_evaluations';

    protected $fillable = [
        'id',
        'employee_id',
        'activity_id',
        'valutatore_id',
        'valutazione',
        'data_valutazione',
        'anno',
        'note',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id', 'id');
    }

    public function activity()
    {
        return $this->belongsTo(HrCompetencyActivity::class, 'activity_id', 'id');
    }

    public function valutatore()
    {
        return $this->belongsTo(User::class, 'valutatore_id', 'id');
    }
}
