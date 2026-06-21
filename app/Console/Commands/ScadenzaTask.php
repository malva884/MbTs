<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class ScadenzaTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scadenza_task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio promemoria scadenza task.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::where('data_scadenza',date('Y-m-d'))
			->where('stato','<>',2)
            ->get();

        foreach ($tasks as $task){
            $users_a = DB::table('task_user_assigneds')
                ->join('users','task_user_assigneds.user_id','users.id')
                ->where('task_id',$task->id)
                ->pluck('email')->toArray();

            $users_r = DB::table('task_uesr_areas')
                ->join('users','task_uesr_areas.user_id','users.id')
                ->where('area_id',$task->area_id)
                ->where('responsabile',true)
                ->pluck('email')->toArray();

            $users = array_merge($users_a, $users_r);

            $content = 'Ciao, Il task '.$task->codice. ' è scaduto.';

            foreach ($users as $user)
                Mail::send('emails/email_white', ['content' => $content], function ($message) use($user,$task){
                    $message
                        ->to($user)
                        ->subject('Task Scaduto '. $task->codice);
                });

        }
    }
}
