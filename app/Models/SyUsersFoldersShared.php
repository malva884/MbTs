<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyUsersFoldersShared extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'id','folder_id','user','ruolo','company_id'
    ];


}
