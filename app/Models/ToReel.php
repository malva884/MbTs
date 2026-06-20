<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ToReel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','bobina','capacita','m3','codice_as','costo','costo_medio','peso','dimensioni','lettera','company_id'];


    static function get_bobina($capacita){

        $result = DB::table('to_reels')->select('id','bobina','lettera','m3','costo','peso')
            ->where('capacita', '>=', $capacita)
            ->orderBY('capacita', 'asc')
            ->first();

        return $result;
    }

}
