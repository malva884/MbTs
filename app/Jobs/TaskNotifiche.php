<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TaskNotifiche implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $oggetto;
    protected $utentiAssegnati;
    protected $responsabili;
    protected $nuoviUtenti;
    protected $testo;
    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct($id, $testo, $oggetto, $utentiAssegnati = false, $responsabili = false, $nuoviUtenti = false)
    {
        $this->id = $id;
        $this->testo = $testo;
        $this->oggetto = $oggetto;
        $this->utentiAssegnati = $utentiAssegnati;
        $this->responsabili = $responsabili;
        $this->nuoviUtenti = $nuoviUtenti;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = DB::table('task_user_assigneds')->select('users.*','task_uesr_areas.responsabile')
            ->join('users','users.id','task_user_assigneds.user_id')
            ->join("task_uesr_areas",function($join){
                $join->on("task_uesr_areas.area_id","=","task_user_assigneds.area_id")
                    ->on("task_uesr_areas.user_id","=","task_user_assigneds.user_id");
            })
            ->where('task_user_assigneds.task_id', $this->id)
            ->Where(function ($query) {
                if ($this->utentiAssegnati) {
                    $query->where('task_uesr_areas.responsabile', false);
                }
            })
            ->Where(function ($query){
                if ($this->responsabili) {
                    $query->where('task_uesr_areas.responsabile', true);
                }
            })
            ->Where(function ($query) {
                if ($this->nuoviUtenti) {
                    $query->where('task_user_assigneds.notification', false);
                }
            })
            ->get();

        $content = $this->testo;
        $task_id = $this->id;
        $oggetto = $this->oggetto;

        foreach ($users as $user){
            $email = 'gregorio.grande@stl.tech'; //$user->email; TODO
            $name = $user->full_name;
            Mail::send('emails/email_task', compact('content','name','task_id'), function ($message) use ($email, $oggetto) {
                $message
                    ->to($email)
                    ->subject($oggetto);
            });
        }

        if ($this->nuoviUtenti)
            DB::table('task_user_assigneds')
                ->where('task_id',$this->id)
                ->update([
                    'notification' => true
                ]);
    }
}
