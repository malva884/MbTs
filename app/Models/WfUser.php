<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfUser extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','user_id','role_id','model','approval_start_date','disabled'];

    public static function notification($model, $tipologia, $role_ids)
    {

    }

}
