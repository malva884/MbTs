<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Utility
{

    static function users_notify($permessi){
        if(!is_array($permessi))
            $permessi = [$permessi];

        $objs = DB::table('system_notifications')->select('nome','email')
            ->where('attivo',1)
            ->whereIn('notifica',$permessi)
            ->get();

        $emails = [];
        foreach ($objs as $obj)
            $emails[] = $obj->email;

        return $emails;
    }
}
