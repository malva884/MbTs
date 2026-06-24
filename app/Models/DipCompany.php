<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DipCompany extends Model
{
    use HasFactory;

    protected $connection = 'mysql_dipendenti';

    protected $table = 'companies';

    protected $guarded = [];
}
