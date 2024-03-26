<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Utility
{

    static function users_notify($permits,$exsternal=null){
        $users = User::permission($permits)->get();
        $result = [];
        if($exsternal){
            $users_external = DB::table('external_user_notifications')->select('name','email')
                ->where('type_notify','=',$exsternal)
                ->where('status','=',true)
                ->get();
            foreach ($users_external as $user)
                $result[] = ['id'=>null,'email'=>$user->email];
        }


        foreach ($users as $user){
            $result[] = $user->email;
            //$result[] = ['id'=>$user->id,'email'=>$user->email];
        }
        return $result;
    }
}
