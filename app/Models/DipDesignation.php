<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DipDesignation extends Model
{
    use HasFactory;

    protected $connection = 'mysql_dipendenti';

    protected $table = 'designations';

    protected $guarded = [];
}
