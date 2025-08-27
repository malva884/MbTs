<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Utility
{

    static function users_notify($permessi,$isName = null){
        if(!is_array($permessi))
            $permessi = [$permessi];

        $objs = DB::table('system_notifications')->select('nome','email')
            ->where('attivo',1)
            ->whereIn('notifica',$permessi)
            ->orderBy('nome')
            ->get();

        $emails = [];
        if(!$isName)
            foreach ($objs as $obj)
                $emails[] = $obj->email;
        else
            return $objs;

        return $emails;
    }
}
