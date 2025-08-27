<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QtSupplierUser extends Model
{

    protected $connection = 'sqlsrv_fornitori';
    protected $table = 'users';

    protected $fillable = [
        'id',
        'supplier_id',
        'nome',
        'email',
        'disattivo',
        'admin',
    ];
}
