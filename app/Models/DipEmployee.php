<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class DipEmployee extends Model
{
    use HasFactory, HasUuids, Notifiable;

    protected $connection = 'mysql_dipendenti';

    protected $table = 'employees';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(DipUser::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(DipDepartment::class, 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(DipDesignation::class, 'designation_id');
    }

    public function company()
    {
        return $this->belongsTo(DipCompany::class, 'company_id');
    }
}
