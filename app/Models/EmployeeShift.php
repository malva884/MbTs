<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    protected $connection = 'mysql_dipendenti';

    protected $table = 'employee_shifts';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'shift_date' => 'date',
    ];
}
