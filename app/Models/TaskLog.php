<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','task_id','user_id','azione','colore','link'];

    static function newTaskLog($task,$user,$azione,$colore='success',$link=null){
        $obj = new TaskLog();
        $obj->task_id = $task;
        $obj->user_id = $user;
        $obj->azione = $azione;
        $obj->colore = $colore;
        $obj->link = $link;
        $obj->save();
    }

    static function time_passed($ptime) {
        $etime = time() - $ptime;
        if ($etime < 1) return '0 seconds';

        $a = array(
            12 * 30 * 24 * 60 * 60 =>  array('anno','anni'),
            30 * 24 * 60 * 60 =>  array('mese','mesi'),
            24 * 60 * 60 => array('giorno','giorni'),
            60 * 60 => array('ora','ore'),
            60 => array('minuto','minuti'),
            1 => array('secondo','secondi')
        );

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $str[1] : $str[0]) . ' fa';
            }
        }
    }
}
