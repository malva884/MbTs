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

        $this->info('[ScadenzaTask] Trovati ' . count($tasks) . ' task scaduti oggi.');

        foreach ($tasks as $task){
            $users_a = DB::table('task_user_assigneds')
                ->join('users','task_user_assigneds.user_id','users.id')
                ->where('task_id',$task->id)
                ->get();

            $users_r = DB::table('task_uesr_areas')
                ->join('users','task_uesr_areas.user_id','users.id')
                ->where('area_id',$task->area_id)
                ->where('responsabile',true)
                ->get();

            $users = $users_a->merge($users_r)->unique('id');

            foreach ($users as $user){
                try {
                    Mail::send('emails/email_task_scaduto', [
                        'task' => $task,
                        'userName' => $user->full_name
                    ], function ($message) use($user, $task){
                        $message
                            ->to($user->email)
                            ->subject('Task Scaduto - ' . $task->codice);
                    });

                } catch (\Exception $e) {
                    $this->error('[ScadenzaTask] Errore invio email a ' . $user->email . ': ' . $e->getMessage());
                }
            }
        }

        return 0;
    }
}
