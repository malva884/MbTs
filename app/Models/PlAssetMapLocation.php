<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PlAssetMapLocation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','map_id','asset_id','utente','ip_address','numero_seriale','data_allocazione','posX','posY',
        'cordinate','tipo_asset','user_id','online'];


    public static function ping ($ip)
    {
        // Run the ping to the IP
        exec ("ping -n 1 -w 1 $ip", $ping_output);

        if(preg_match("/Risposta/i", $ping_output[2]))
            return true;
        else
            return false;
    }
}
