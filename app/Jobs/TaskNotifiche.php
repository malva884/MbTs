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
    protected $task;

    /**
     * Create a new job instance.
     */
    public function __construct($task, $testo_notifica, $oggetto_notifica, $utentiAssegnatiT = false, $responsabiliT = false, $nuoviUtentiT = false)
    {
		
        $this->task = $task;
        $this->testo = $testo_notifica;
        $this->oggetto = $oggetto_notifica;
        $this->utentiAssegnati = $utentiAssegnatiT;
        $this->responsabili = $responsabiliT;
        $this->nuoviUtenti = $nuoviUtentiT;
		
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
			$users = DB::table('task_user_assigneds')->select('users.*','task_uesr_areas.responsabile','task_uesr_areas.area_id')
				->join('users','users.id','task_user_assigneds.user_id')
				->join("task_uesr_areas",function($join){
					$join->on("task_uesr_areas.area_id","=","task_user_assigneds.area_id")
						->on("task_uesr_areas.user_id","=","task_user_assigneds.user_id");
				})
				->where('task_user_assigneds.task_id', $this->task->id)
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
			
		

        foreach ($users as $user){
            Mail::send('emails/email_task_assegnato', [
                'task' => $this->task,
                'userName' => $user->full_name,
                'message' => $this->testo
            ], function ($message) use ($user) {
                $message
                    ->to($user->email)
                    ->subject($this->oggetto);
            });
        }

        if ($this->nuoviUtenti)
            DB::table('task_user_assigneds')
                ->where('task_id',$this->task->id)
                ->update([
                    'notification' => true
                ]);
    }
}
